<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use Database\Seeders\TemplateLibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class EventEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Event editor integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_editor_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $this->get("/app/events/{$event->getKey()}/editor")->assertRedirect(route('login'));
    }

    public function test_owner_receives_a_complete_editor_document_and_active_library(): void
    {
        $this->seed(TemplateLibrarySeeder::class);
        $owner = User::factory()->create();
        $template = Template::query()->where('slug', 'powder-gold')->sole();
        $event = $this->eventFromTemplate($owner, $template);

        $response = $this->actingAs($owner)->withoutVite()->get("/app/events/{$event->getKey()}/editor");

        $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('App/Editor', false)
            ->where('event.id', (string) $event->getKey())
            ->where('document.schemaVersion', 1)
            ->where('document.canvas.width', 1080)
            ->where('document.canvas.height', 1920)
            ->where('revision', 1)
            ->has('assets', 17)
            ->has('collections', 4)
            ->has('recommendedAssetIds', 5));

        $serialized = $response->getContent();
        $this->assertStringNotContainsString('original_path', $serialized);
        $this->assertStringNotContainsString('editor/assets/v1/', $serialized);
    }

    public function test_inactive_referenced_assets_remain_renderable_without_exposing_unrelated_assets(): void
    {
        $this->seed(TemplateLibrarySeeder::class);
        $owner = User::factory()->create();
        $template = Template::query()->where('slug', 'powder-gold')->sole();
        $event = $this->eventFromTemplate($owner, $template);
        $referenced = TemplateAsset::query()->where('slug', 'powder-botanical-frame')->sole();
        $unrelated = TemplateAsset::query()->where('slug', 'wax-seal')->sole();
        $referenced->forceFill(['is_active' => false])->save();
        $unrelated->forceFill(['is_active' => false])->save();

        $response = $this->actingAs($owner)->withoutVite()->get("/app/events/{$event->getKey()}/editor");
        $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('assets', 16)
            ->where('assets', fn ($assets): bool => collect($assets)->pluck('slug')->contains('powder-botanical-frame')
                && ! collect($assets)->pluck('slug')->contains('wax-seal')));
    }

    public function test_editor_is_private_to_its_owner_even_for_an_admin(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $event = Event::factory()->create(['user_id' => $owner->getKey()]);

        $this->actingAs($other)->get("/app/events/{$event->getKey()}/editor")->assertForbidden();
        $this->actingAs($admin)->get("/app/events/{$event->getKey()}/editor")->assertForbidden();
    }

    public function test_visual_assets_use_full_size_raster_previews_while_originals_stay_private(): void
    {
        $this->seed(TemplateLibrarySeeder::class);

        TemplateAsset::query()->where('type', '!=', 'font')->each(function (TemplateAsset $asset): void {
            $this->assertStringEndsWith('.png', $asset->preview_path);
            $this->assertFileExists(public_path($asset->preview_path));
            $dimensions = getimagesize(public_path($asset->preview_path));
            $this->assertIsArray($dimensions);
            $this->assertSame($asset->width, $dimensions[0]);
            $this->assertSame($asset->height, $dimensions[1]);
        });
    }

    private function eventFromTemplate(User $owner, Template $template): Event
    {
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

        return $event;
    }
}
