<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Users\Actions\ListUserSessionsAction;
use App\Domains\Users\Actions\LogoutOtherSessionsAction;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class AuthenticationFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Authentication integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_registration_creates_only_an_active_customer(): void
    {
        $response = $this->post('/register', [
            'name' => 'بشرى الفيتوري',
            'email' => 'Owner@Example.Test',
            'phone' => '+218 91 000 0000',
            'password' => 'StrongPass!2026',
            'password_confirmation' => 'StrongPass!2026',
            'role' => 'admin',
            'status' => 'suspended',
        ]);

        $response->assertRedirect('/app');
        $this->assertAuthenticated();

        $user = User::query()->sole();
        $this->assertSame('owner@example.test', $user->email);
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
    }

    public function test_active_user_can_login_and_suspended_user_cannot(): void
    {
        $active = User::factory()->create([
            'email' => 'active@example.test',
            'password' => 'StrongPass!2026',
        ]);

        $this->post('/login', [
            'email' => 'ACTIVE@example.test',
            'password' => 'StrongPass!2026',
        ])->assertRedirect('/app');
        $this->assertAuthenticatedAs($active);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();

        User::factory()->create([
            'email' => 'suspended@example.test',
            'password' => 'StrongPass!2026',
            'status' => UserStatus::Suspended,
            'suspended_at' => now(),
        ]);

        $this->post('/login', [
            'email' => 'suspended@example.test',
            'password' => 'StrongPass!2026',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_suspended_authenticated_session_is_invalidated(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Suspended,
            'suspended_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/app')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_password_reset_request_does_not_reveal_if_email_exists(): void
    {
        User::factory()->create(['email' => 'known@example.test']);

        $known = $this->post('/forgot-password', ['email' => 'known@example.test']);
        $unknown = $this->post('/forgot-password', ['email' => 'unknown@example.test']);

        $known->assertSessionHasNoErrors()->assertSessionHas('status', 'إذا كان البريد مسجلاً، سيصلك رابط الاستعادة.');
        $unknown->assertSessionHasNoErrors()->assertSessionHas('status', 'إذا كان البريد مسجلاً، سيصلك رابط الاستعادة.');
    }

    public function test_login_is_rate_limited_by_normalized_email_and_ip(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', [
                'email' => 'RATE@example.test',
                'password' => 'wrong-password',
            ]);
        }

        $this->assertTrue(RateLimiter::tooManyAttempts('rate@example.test|127.0.0.1', 5));
        $this->post('/login', [
            'email' => 'rate@example.test',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_database_sessions_can_be_listed_and_other_devices_logged_out(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['password' => 'StrongPass!2026']);

        DB::table('sessions')->insert([
            [
                'id' => 'current-session',
                'user_id' => $user->getKey(),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Current browser',
                'payload' => 'test',
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'other-session',
                'user_id' => $user->getKey(),
                'ip_address' => '127.0.0.2',
                'user_agent' => 'Other browser',
                'payload' => 'test',
                'last_activity' => now()->subMinute()->timestamp,
            ],
        ]);

        $sessions = app(ListUserSessionsAction::class)->execute($user, 'current-session');
        $this->assertCount(2, $sessions);
        $this->assertTrue($sessions[0]->isCurrentDevice);
        $this->assertNotSame('current-session', $sessions[0]->fingerprint);

        auth()->guard('web')->setUser($user);
        app(LogoutOtherSessionsAction::class)->execute($user, 'StrongPass!2026', 'current-session');

        $this->assertDatabaseHas('sessions', ['id' => 'current-session']);
        $this->assertDatabaseMissing('sessions', ['id' => 'other-session']);
    }

    public function test_email_can_be_verified_and_changing_it_requires_verification_again(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create(['email' => 'owner@example.test']);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->getKey(), 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)->get($verificationUrl)->assertRedirect('/app?verified=1');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $this->put('/user/profile-information', [
            'name' => 'الاسم الجديد',
            'email' => 'new-owner@example.test',
            'phone' => null,
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('new-owner@example.test', $user->email);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
