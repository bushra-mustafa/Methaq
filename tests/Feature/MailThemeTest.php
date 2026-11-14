<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Users\Models\User;
use App\Mail\WelcomeMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class MailThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Mail tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_signing_up_sends_the_themed_welcome_email_once(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'new@example.test', 'name' => 'نورة']);

        event(new Registered($user));

        Mail::assertSent(WelcomeMail::class, 1);
        Mail::assertSent(WelcomeMail::class, function (WelcomeMail $mail): bool {
            $html = $mail->render();

            return $mail->hasTo('new@example.test')
                && str_contains($html, 'ميثاق')
                && str_contains($html, url('/app'))
                && str_contains($html, 'dir="rtl"');
        });
    }

    public function test_a_failing_mailer_never_blocks_sign_up(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));

        event(new Registered(User::factory()->create()));

        $this->assertDatabaseCount('users', 1);
    }

    public function test_the_password_reset_email_uses_the_methaq_theme_and_a_relative_reset_link(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.test']);

        $message = (new ResetPassword('tok-123'))->toMail($user);
        $html = (string) $message->render();

        $this->assertSame('إعادة تعيين كلمة المرور | ميثاق', $message->subject);
        $this->assertStringContainsString('/reset-password/tok-123', $html);
        $this->assertStringContainsString('reset%40example.test', $html);
        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('ميثاق', $html);
    }
}
