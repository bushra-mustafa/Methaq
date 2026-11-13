<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Actions\CreateTemplateAction;
use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Editor\Services\BlankDesignFactory;
use App\Domains\Events\Enums\EventCategory;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use App\Filament\Resources\Templates\Pages\ManageTemplates;
use Database\Seeders\TemplateLibrarySeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Livewire;
use Tests\TestCase;

final class TemplateStudioTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Template studio tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_an_administrator_opens_the_template_in_the_editor(): void
    {
        $template = $this->template();

        $this->actingAs($this->administrator())->withoutVite()->get("/app/studio/templates/{$template->getKey()}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('App/Editor', false)
                ->where('mode', 'template')
                ->where('template.name', $template->name)
                ->where('revision', 1)
                ->has('assets')
                ->missing('event'));
    }

    public function test_only_an_administrator_with_confirmed_two_factor_may_design(): void
    {
        $template = $this->template();
        $url = "/app/studio/templates/{$template->getKey()}";

        $this->get($url)->assertRedirect();
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create())->patchJson("{$url}/design", ['document' => [], 'expectedRevision' => 1])->assertForbidden();
    }

    public function test_saving_a_design_bumps_the_revision_and_is_audited(): void
    {
        $admin = $this->administrator();
        $template = $this->template();
        $document = $this->documentWithText($template, 'نص جديد');

        $this->actingAs($admin)->patchJson("/app/studio/templates/{$template->getKey()}/design", ['document' => $document, 'expectedRevision' => 1])
            ->assertOk()
            ->assertJsonPath('revision', 2);

        $template->refresh();
        $this->assertSame(2, $template->design_revision);
        $this->assertSame('نص جديد', $template->default_design_json['layers'][0]['content']);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::TemplateDesigned->value, 'actor_id' => $admin->getKey(), 'subject_id' => $template->getKey()]);
    }

    public function test_a_stale_revision_is_refused_with_the_current_snapshot(): void
    {
        $template = $this->template();
        $template->forceFill(['design_revision' => 3])->save();

        $this->actingAs($this->administrator())
            ->patchJson("/app/studio/templates/{$template->getKey()}/design", ['document' => $this->documentWithText($template, 'x'), 'expectedRevision' => 2])
            ->assertStatus(409)
            ->assertJsonPath('snapshot.revision', 3);
        $this->assertSame(3, $template->refresh()->design_revision);
    }

    public function test_a_design_that_points_at_a_missing_asset_is_refused(): void
    {
        $template = $this->template();
        $document = $this->documentWithText($template, 'x');
        $document['canvas']['layers'][0]['font']['assetId'] = '999999';

        $this->actingAs($this->administrator())
            ->patchJson("/app/studio/templates/{$template->getKey()}/design", ['document' => $document, 'expectedRevision' => 1])
            ->assertStatus(422);
        $this->assertSame(1, $template->refresh()->design_revision);
    }

    public function test_a_new_template_starts_blank_hidden_and_unpriced(): void
    {
        $admin = $this->administrator();

        $template = app(CreateTemplateAction::class)->execute($admin, '  قالب تجريبي ', EventCategory::Graduation);

        $this->assertSame('قالب تجريبي', $template->name);
        $this->assertFalse($template->is_active);
        $this->assertSame(0, $template->price_minor);
        $this->assertSame(CreateTemplateAction::PLACEHOLDER_PREVIEW, $template->thumbnail_path);
        $this->assertFileExists(public_path($template->thumbnail_path));
        $this->assertSame([], $template->default_design_json['layers']);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::TemplateCreated->value, 'subject_id' => $template->getKey()]);
        $this->assertTrue(Template::query()->onSale()->doesntExist());
    }

    public function test_a_customer_cannot_create_a_template(): void
    {
        $this->expectException(AuthorizationException::class);

        app(CreateTemplateAction::class)->execute(User::factory()->create(), 'x', EventCategory::Wedding);
    }

    public function test_the_preview_is_stored_as_a_png_with_twins_and_replaces_the_previous_one(): void
    {
        Storage::fake('public');
        $template = $this->template();
        $url = "/app/studio/templates/{$template->getKey()}/thumbnail";
        $admin = $this->administrator();

        $this->actingAs($admin)->postJson($url, ['preview' => $this->png(540, 960)])->assertOk();
        $first = $template->refresh()->thumbnail_path;
        $this->assertStringStartsWith('storage/editor/templates/'.$template->slug.'-', $first);
        Storage::disk('public')->assertExists(substr($first, strlen('storage/')));
        Storage::disk('public')->assertExists(substr($first, strlen('storage/'), -4).'.webp');

        $this->actingAs($admin)->postJson($url, ['preview' => $this->png(540, 960)])->assertOk();
        Storage::disk('public')->assertMissing(substr($first, strlen('storage/')));
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::TemplateThumbnailUpdated->value, 'subject_id' => $template->getKey()]);
    }

    public function test_the_preview_must_be_a_real_540_by_960_png(): void
    {
        Storage::fake('public');
        $template = $this->template();
        $url = "/app/studio/templates/{$template->getKey()}/thumbnail";
        $admin = $this->administrator();
        $before = $template->thumbnail_path;

        $this->actingAs($admin)->postJson($url, ['preview' => $this->png(1080, 1920)])->assertStatus(422);
        $this->actingAs($admin)->postJson($url, ['preview' => UploadedFile::fake()->createWithContent('preview.png', '<?php echo 1;')])->assertStatus(422);
        $this->actingAs(User::factory()->create())->postJson($url, ['preview' => $this->png(540, 960)])->assertForbidden();

        $this->assertSame($before, $template->refresh()->thumbnail_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_reseeding_keeps_a_template_an_administrator_redesigned(): void
    {
        $this->seed(TemplateLibrarySeeder::class);
        $template = Template::query()->where('slug', 'powder-gold')->sole();
        $template->forceFill(['design_revision' => 2, 'default_design_json' => ['layers' => [], 'width' => 1080, 'height' => 1920, 'schemaVersion' => 1], 'thumbnail_path' => 'storage/editor/templates/powder-gold-abc.png'])->save();

        $this->seed(TemplateLibrarySeeder::class);

        $template->refresh();
        $this->assertSame([], $template->default_design_json['layers']);
        $this->assertSame('storage/editor/templates/powder-gold-abc.png', $template->thumbnail_path);
        $untouched = Template::query()->where('slug', 'jasmine-white')->sole();
        $this->assertNotEmpty($untouched->default_design_json['layers']);
    }

    public function test_the_templates_page_creates_a_template_and_opens_the_studio(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs($this->administrator());

        Livewire::test(ManageTemplates::class)
            ->callAction('create', ['name' => 'قالب الاستوديو', 'category' => 'newborn'])
            ->assertHasNoActionErrors();

        $created = Template::query()->where('name', 'قالب الاستوديو')->sole();
        $this->assertSame(EventCategory::Newborn, $created->category);
        $this->assertFalse($created->is_active);
    }

    public function test_every_template_row_offers_the_studio(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs($this->administrator());
        $template = $this->template();

        Livewire::test(ManageTemplates::class)
            ->assertTableActionVisible('design', $template)
            ->assertTableActionHasUrl('design', route('app.studio.templates.show', $template), $template);
    }

    private function template(): Template
    {
        $this->seed(TemplateLibrarySeeder::class);
        $blank = (new BlankDesignFactory)->make()->toStorageColumns();

        return Template::factory()->create([
            'default_design_json' => $blank['design_json'],
            'default_scene_json' => $blank['scene_json'],
            'default_palette_json' => $blank['palette_json'],
            'schema_version' => $blank['schema_version'],
            'thumbnail_path' => CreateTemplateAction::PLACEHOLDER_PREVIEW,
        ]);
    }

    /** @return array<string, mixed> */
    private function documentWithText(Template $template, string $content): array
    {
        $font = TemplateAsset::query()->where('slug', 'font-amiri')->sole();
        $document = [
            'schemaVersion' => $template->schema_version,
            'canvas' => $template->default_design_json,
            'palette' => $template->default_palette_json,
            'scene' => $template->default_scene_json,
        ];
        $document['canvas']['layers'] = [[
            'id' => 'text-1', 'type' => 'text', 'locked' => false, 'visible' => true,
            'frame' => ['x' => 100, 'y' => 200, 'width' => 800, 'height' => 120, 'scaleX' => 1, 'scaleY' => 1, 'rotation' => 0, 'opacity' => 1],
            'content' => $content, 'language' => 'ar', 'direction' => 'rtl', 'alignment' => 'center',
            'font' => ['assetId' => (string) $font->getKey(), 'version' => $font->asset_version],
            'fontSize' => 60, 'fontWeight' => 400, 'fill' => ['source' => 'palette', 'role' => 'primaryText'],
        ]];

        return $document;
    }

    private function png(int $width, int $height): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return UploadedFile::fake()->createWithContent('preview.png', $bytes);
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }
}
