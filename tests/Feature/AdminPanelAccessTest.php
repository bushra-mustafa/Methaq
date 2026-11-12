<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Tests\TestCase;

final class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Admin panel integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_guests_are_sent_to_the_application_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_confirmed_administrator_opens_the_panel(): void
    {
        $this->actingAs($this->administrator())->get('/admin')->assertOk();
    }

    public function test_customers_and_unconfirmed_or_suspended_administrators_are_refused(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();

        $withoutTwoFactor = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($withoutTwoFactor)->get('/admin')->assertForbidden();

        $suspended = $this->administrator();
        $suspended->forceFill(['status' => UserStatus::Suspended])->save();
        $this->actingAs($suspended->fresh())->get('/admin')->assertForbidden();
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }
}
