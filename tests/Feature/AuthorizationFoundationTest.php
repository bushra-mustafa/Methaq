<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\Template;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Tests\TestCase;

final class AuthorizationFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Authorization integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_event_policy_is_owner_only_even_for_an_admin(): void
    {
        $owner = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $event = Event::factory()->create(['user_id' => $owner->getKey()]);

        $this->assertTrue($owner->can('view', $event));
        $this->assertTrue($owner->can('update', $event));
        $this->assertFalse($otherCustomer->can('view', $event));
        $this->assertFalse($admin->can('view', $event));
    }

    public function test_suspended_admin_cannot_use_content_policy(): void
    {
        $template = Template::factory()->create(['is_active' => false]);
        $activeAdmin = User::factory()->create(['role' => UserRole::Admin]);
        $suspendedAdmin = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Suspended,
            'suspended_at' => now(),
        ]);

        $this->assertTrue($activeAdmin->can('view', $template));
        $this->assertFalse($suspendedAdmin->can('view', $template));
        $this->assertFalse($suspendedAdmin->can('update', $template));
    }

    public function test_admin_area_requires_role_and_confirmed_two_factor_authentication(): void
    {
        $customer = User::factory()->create();
        $this->actingAs($customer)->get('/app/admin')->assertForbidden();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->get('/app/admin')->assertRedirect(route('app.profile'));

        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        $this->actingAs($admin->fresh())->get('/app/admin')->assertOk();
    }

    public function test_trusted_command_promotes_an_active_user_and_audits_it(): void
    {
        $user = User::factory()->create(['email' => 'operator@example.test']);

        $this->artisan('methaq:promote-admin', ['email' => 'OPERATOR@example.test'])
            ->assertSuccessful();

        $this->assertSame(UserRole::Admin, $user->fresh()->role);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.promoted_to_admin',
            'subject_type' => 'user',
            'subject_id' => $user->getKey(),
        ]);
    }
}
