<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Actions\ListFeaturedTemplatesAction;
use App\Domains\Editor\Enums\TemplateFeature;
use App\Domains\Editor\Models\Template;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use App\Filament\Resources\Templates\Pages\ManageTemplates;
use Database\Seeders\TemplateLibrarySeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Livewire;
use Tests\TestCase;

final class FilamentTemplateResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Filament integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel('admin');
    }

    public function test_administrator_sees_every_template_and_can_start_a_new_one_but_not_edit_or_delete_rows(): void
    {
        $this->seed(TemplateLibrarySeeder::class);
        Template::query()->update(['price_minor' => 25_000]);

        $this->actingAs($this->administrator())->get('/admin/templates')
            ->assertOk()
            ->assertSee('ليلة عنابية')
            ->assertSee('ياسمين أبيض');

        Livewire::test(ManageTemplates::class)
            ->assertActionExists('create')
            ->assertTableActionDoesNotExist('delete')
            ->assertCanSeeTableRecords(Template::query()->get());
    }

    public function test_price_and_locked_features_are_saved_in_dinars_with_an_audit_entry(): void
    {
        $admin = $this->administrator();
        $template = Template::factory()->create();
        $this->actingAs($admin);

        Livewire::test(ManageTemplates::class)
            ->callAction(TestAction::make('commerce')->table($template), data: [
                'price' => 25.5,
                'locked_features' => [TemplateFeature::Music->value, TemplateFeature::Rsvp->value],
            ])
            ->assertHasNoFormErrors();

        $template->refresh();
        $this->assertSame(25_500, $template->price_minor);
        $this->assertTrue($template->isFeatureLocked(TemplateFeature::Music));
        $this->assertTrue($template->isFeatureLocked(TemplateFeature::Rsvp));
        $this->assertFalse($template->isFeatureLocked(TemplateFeature::HdDownload));
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->getKey(),
            'action' => 'template.commerce_updated',
            'subject_id' => $template->getKey(),
        ]);

        Livewire::test(ManageTemplates::class)
            ->callAction(TestAction::make('commerce')->table($template), data: ['price' => -1, 'locked_features' => []])
            ->assertHasFormErrors(['price']);
        $this->assertSame(25_500, $template->refresh()->price_minor);
    }

    public function test_visibility_and_featuring_require_a_reason_and_reuse_the_audited_actions(): void
    {
        $admin = $this->administrator();
        $template = Template::factory()->create(['is_active' => true, 'is_featured' => true]);
        $this->actingAs($admin);

        Livewire::test(ManageTemplates::class)
            ->callAction(TestAction::make('visibility')->table($template), data: ['reason' => ''])
            ->assertHasFormErrors(['reason']);

        Livewire::test(ManageTemplates::class)
            ->callAction(TestAction::make('visibility')->table($template), data: ['reason' => 'مراجعة التصميم'])
            ->callAction(TestAction::make('featured')->table($template), data: ['reason' => 'نعرض قوالب الحنّة'])
            ->assertHasNoFormErrors();

        $template->refresh();
        $this->assertFalse($template->is_active);
        $this->assertFalse($template->is_featured);
        $this->assertDatabaseHas('audit_logs', ['action' => 'template.deactivated', 'subject_id' => $template->getKey(), 'reason' => 'مراجعة التصميم']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'template.unfeatured', 'subject_id' => $template->getKey()]);
    }

    public function test_drag_and_drop_order_drives_the_home_page(): void
    {
        $this->actingAs($this->administrator());
        [$first, $second, $third] = Template::factory()->count(3)->create(['is_active' => true, 'is_featured' => true])->all();

        Livewire::test(ManageTemplates::class)->call('reorderTable', [(string) $third->getKey(), (string) $first->getKey(), (string) $second->getKey()]);

        $this->assertSame([1, 2, 3], [$third->refresh()->sort_order, $first->refresh()->sort_order, $second->refresh()->sort_order]);
        $featuredIds = array_column(app(ListFeaturedTemplatesAction::class)->execute(), 'id');
        $this->assertSame([(string) $third->getKey(), (string) $first->getKey(), (string) $second->getKey()], $featuredIds);
    }

    public function test_reseeding_keeps_the_administrators_price_order_and_locks(): void
    {
        $this->seed(TemplateLibrarySeeder::class);
        Template::query()->update(['price_minor' => 25_000]);
        $template = Template::query()->where('slug', 'jasmine-white')->sole();
        $template->forceFill(['price_minor' => 40_000, 'sort_order' => 1, 'locked_features' => [TemplateFeature::HdDownload]])->save();

        $this->seed(TemplateLibrarySeeder::class);

        $template->refresh();
        $this->assertSame(40_000, $template->price_minor);
        $this->assertSame(1, $template->sort_order);
        $this->assertTrue($template->isFeatureLocked(TemplateFeature::HdDownload));
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }
}
