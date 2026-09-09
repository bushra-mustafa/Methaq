<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use Database\Seeders\TemplateLibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SaveEventDesignTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Design save integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_owner_can_save_the_complete_document_and_increment_revision(): void
    {
        [$owner, $event, $document] = $this->eventFixture();
        $document['palette']['values']['background'] = '#4b172a';
        $document['canvas']['layers'][1]['content'] = 'نص محفوظ على الخادم';

        $this->actingAs($owner)->patchJson($this->endpoint($event), [
            'document' => $document,
            'expectedRevision' => 1,
        ])->assertOk()
            ->assertJsonPath('revision', 2)
            ->assertJsonPath('document.palette.values.background', '#4b172a')
            ->assertJsonPath('document.canvas.layers.1.content', 'نص محفوظ على الخادم');

        $design = $event->design()->sole();
        $this->assertSame(2, $design->revision);
        $this->assertSame('#4b172a', $design->palette_json['values']['background']);
        $this->assertSame('نص محفوظ على الخادم', $design->design_json['layers'][1]['content']);
    }

    public function test_stale_revision_returns_current_snapshot_without_overwriting_it(): void
    {
        [$owner, $event, $document] = $this->eventFixture();
        $first = $document;
        $first['palette']['values']['accent'] = '#112233';
        $second = $document;
        $second['palette']['values']['accent'] = '#445566';

        $this->actingAs($owner)->patchJson($this->endpoint($event), [
            'document' => $first,
            'expectedRevision' => 1,
        ])->assertOk()->assertJsonPath('revision', 2);

        $this->actingAs($owner)->patchJson($this->endpoint($event), [
            'document' => $second,
            'expectedRevision' => 1,
        ])->assertConflict()
            ->assertJsonPath('snapshot.revision', 2)
            ->assertJsonPath('snapshot.document.palette.values.accent', '#112233');

        $this->assertSame('#112233', $event->design()->sole()->palette_json['values']['accent']);
    }

    public function test_design_save_is_private_to_the_owner(): void
    {
        [$owner, $event, $document] = $this->eventFixture();
        $other = User::factory()->create();

        $this->patchJson($this->endpoint($event), ['document' => $document, 'expectedRevision' => 1])->assertUnauthorized();
        $this->actingAs($other)->patchJson($this->endpoint($event), ['document' => $document, 'expectedRevision' => 1])->assertForbidden();
        $this->actingAs($owner)->patchJson($this->endpoint($event), ['document' => $document, 'expectedRevision' => 1])->assertOk();
    }

    public function test_unknown_properties_markup_and_server_owned_fields_are_rejected(): void
    {
        [$owner, $event, $document] = $this->eventFixture();
        $withUnknownProperty = $document;
        $withUnknownProperty['canvas']['layers'][0]['externalUrl'] = 'https://example.test/file.svg';

        $this->actingAs($owner)->patchJson($this->endpoint($event), [
            'document' => $withUnknownProperty,
            'expectedRevision' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('document');

        $withMarkup = $document;
        $withMarkup['canvas']['layers'][1]['content'] = '<img src=x onerror=alert(1)>';
        $this->actingAs($owner)->patchJson($this->endpoint($event), [
            'document' => $withMarkup,
            'expectedRevision' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('document');

        $this->actingAs($owner)->patchJson($this->endpoint($event), [
            'document' => $document,
            'expectedRevision' => 1,
            'is_paid' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('document');

        $this->assertSame(1, $event->design()->sole()->revision);
    }

    public function test_asset_identifiers_versions_types_and_inactive_additions_are_enforced(): void
    {
        [$owner, $event, $document] = $this->eventFixture();
        $unknown = $document;
        $unknown['canvas']['layers'][0]['asset']['assetId'] = '999999';
        $this->actingAs($owner)->patchJson($this->endpoint($event), ['document' => $unknown, 'expectedRevision' => 1])
            ->assertUnprocessable();

        $wrongVersion = $document;
        $wrongVersion['canvas']['layers'][0]['asset']['version'] = 999;
        $this->actingAs($owner)->patchJson($this->endpoint($event), ['document' => $wrongVersion, 'expectedRevision' => 1])
            ->assertUnprocessable();

        $font = TemplateAsset::query()->where('slug', 'font-amiri')->sole();
        $wrongType = $document;
        $wrongType['canvas']['layers'][0]['asset'] = ['assetId' => (string) $font->getKey(), 'version' => $font->asset_version];
        $this->actingAs($owner)->patchJson($this->endpoint($event), ['document' => $wrongType, 'expectedRevision' => 1])
            ->assertUnprocessable();

        $inactive = TemplateAsset::query()->where('slug', 'wax-seal')->sole();
        $inactive->forceFill(['is_active' => false])->save();
        $inactiveAddition = $document;
        $inactiveAddition['canvas']['layers'][] = [
            'id' => 'inactive_asset',
            'type' => 'image',
            'frame' => ['x' => 100, 'y' => 100, 'width' => 200, 'height' => 200, 'scaleX' => 1, 'scaleY' => 1, 'rotation' => 0, 'opacity' => 1],
            'locked' => false,
            'visible' => true,
            'asset' => ['assetId' => (string) $inactive->getKey(), 'version' => $inactive->asset_version],
            'fit' => 'contain',
        ];
        $this->actingAs($owner)->patchJson($this->endpoint($event), ['document' => $inactiveAddition, 'expectedRevision' => 1])
            ->assertUnprocessable();

        $this->assertSame(1, $event->design()->sole()->revision);
    }

    /** @return array{User, Event, array<string, mixed>} */
    private function eventFixture(): array
    {
        $this->seed(TemplateLibrarySeeder::class);
        $owner = User::factory()->create();
        $template = Template::query()->where('slug', 'powder-gold')->sole();
        $event = Event::factory()->create([
            'user_id' => $owner->getKey(),
            'template_id' => $template->getKey(),
            'category' => $template->category,
        ]);
        EventDesign::query()->forceCreate([
            'event_id' => $event->getKey(),
            'design_json' => $template->default_design_json,
            'scene_json' => $template->default_scene_json,
            'palette_json' => $template->default_palette_json,
            'schema_version' => $template->schema_version,
            'revision' => 1,
        ]);
        $document = [
            'schemaVersion' => $template->schema_version,
            'canvas' => $template->default_design_json,
            'palette' => $template->default_palette_json,
            'scene' => $template->default_scene_json,
        ];

        return [$owner, $event, $document];
    }

    private function endpoint(Event $event): string
    {
        return "/app/events/{$event->getKey()}/design";
    }
}
