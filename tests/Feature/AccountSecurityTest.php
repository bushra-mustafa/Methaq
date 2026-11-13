<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'old-password-for-tests-1';

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Account security tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_changing_the_email_needs_the_current_password(): void
    {
        $user = $this->user();

        $this->actingAs($user)->put('/user/profile-information', $this->profile($user, ['email' => 'new@example.test']))
            ->assertSessionHasErrors('current_password', errorBag: 'updateProfileInformation');
        $this->actingAs($user)->put('/user/profile-information', $this->profile($user, ['email' => 'new@example.test', 'current_password' => 'not-the-password']))
            ->assertSessionHasErrors('current_password', errorBag: 'updateProfileInformation');

        $this->assertNotSame('new@example.test', $user->refresh()->email);
    }

    public function test_the_email_changes_with_the_right_password_and_is_no_longer_marked_confirmed(): void
    {
        $user = $this->user();

        $this->actingAs($user)->put('/user/profile-information', $this->profile($user, ['email' => 'New@Example.test', 'current_password' => self::PASSWORD]))
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('new@example.test', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_other_profile_fields_change_without_a_password(): void
    {
        $user = $this->user();

        $this->actingAs($user)->put('/user/profile-information', $this->profile($user, ['name' => 'اسم جديد']))
            ->assertSessionHasNoErrors();

        $this->assertSame('اسم جديد', $user->refresh()->name);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_a_password_change_signs_out_other_devices_but_not_this_one(): void
    {
        $user = $this->user();
        $oldHash = $user->password;

        $this->actingAs($user)->put('/user/password', [
            'current_password' => self::PASSWORD,
            'password' => 'A-brand-new-Password-2',
            'password_confirmation' => 'A-brand-new-Password-2',
        ])->assertSessionHasNoErrors();
        $this->get('/app/profile')->assertOk();

        $this->flushSession();
        $this->withSession(['password_hash_web' => $oldHash])->actingAs($user->fresh())->get('/app/profile')->assertRedirect('/login');
    }

    private function user(): User
    {
        return User::factory()->create(['password' => self::PASSWORD, 'email' => 'owner@example.test']);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function profile(User $user, array $overrides = []): array
    {
        return array_merge(['name' => $user->name, 'email' => $user->email, 'phone' => ''], $overrides);
    }
}
