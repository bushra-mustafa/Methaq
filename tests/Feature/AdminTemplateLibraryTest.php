<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\Template;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use Database\Seeders\TemplateLibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Tests\TestCase;

final class AdminTemplateLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Admin library integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_customer_cannot_manage_the_library(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/app/admin/library')->assertForbidden();
    }

    public function test_confirmed_admin_can_view_all_library_records(): void
    {
        $this->seed(TemplateLibrarySeeder::class);
        $admin = $this->confirmedAdmin();

        $this->actingAs($admin)->withoutVite()->get('/app/admin/library')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('App/Admin/Library/Index', false)
                ->has('templates', 4)
                ->has('assets', 12)
                ->has('collections', 3));
    }

    public function test_admin_status_change_requires_a_reason_and_is_audited_once(): void
    {
        $this->seed(TemplateLibrarySeeder::class);
        $admin = $this->confirmedAdmin();
        $template = Template::query()->where('slug', 'powder-gold')->sole();
        $url = "/app/admin/library/template/{$template->getKey()}/status";

        $this->actingAs($admin)->patch($url, ['is_active' => false, 'reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->actingAs($admin)->patch($url, [
            'is_active' => false,
            'reason' => 'إيقاف مؤقت لمراجعة التصميم',
        ])->assertSessionHas('status', 'library-status-updated');

        $this->assertFalse($template->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->getKey(),
            'action' => 'template.deactivated',
            'subject_type' => 'template',
            'subject_id' => $template->getKey(),
            'reason' => 'إيقاف مؤقت لمراجعة التصميم',
        ]);

        $this->actingAs($admin)->patch($url, [
            'is_active' => false,
            'reason' => 'طلب مكرر',
        ])->assertSessionHas('status', 'library-status-updated');

        $this->assertDatabaseCount('audit_logs', 1);
    }

    private function confirmedAdmin(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }
}
