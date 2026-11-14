<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class TurnstileTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFY = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Turnstile tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_nothing_changes_while_the_keys_are_not_set(): void
    {
        Http::fake();

        $this->post('/register', $this->registration())->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'guest@example.com']);
        Http::assertNothingSent();
    }

    public function test_sign_up_needs_a_passed_challenge_once_the_keys_are_set(): void
    {
        $this->enable();
        Http::fake();

        $this->post('/register', $this->registration())->assertSessionHasErrors('cf-turnstile-response');

        $this->assertDatabaseMissing('users', ['email' => 'guest@example.com']);
        Http::assertNothingSent();
    }

    public function test_a_valid_token_lets_the_sign_up_through_and_cloudflare_receives_the_secret_and_token(): void
    {
        $this->enable();
        Http::fake([self::VERIFY => Http::response(['success' => true])]);

        $this->post('/register', $this->registration(['cf-turnstile-response' => 'good-token']))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'guest@example.com']);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::VERIFY
            && $request['secret'] === 'secret-key'
            && $request['response'] === 'good-token');
    }

    public function test_a_refused_token_blocks_the_sign_up(): void
    {
        $this->enable();
        Http::fake([self::VERIFY => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']])]);

        $this->post('/register', $this->registration(['cf-turnstile-response' => 'bad-token']))->assertSessionHasErrors('cf-turnstile-response');

        $this->assertDatabaseMissing('users', ['email' => 'guest@example.com']);
    }

    public function test_an_unreachable_cloudflare_fails_closed(): void
    {
        $this->enable();
        Http::fake([self::VERIFY => fn () => throw new ConnectionException('timeout')]);

        $this->post('/register', $this->registration(['cf-turnstile-response' => 'any-token']))->assertSessionHasErrors('cf-turnstile-response');

        $this->assertDatabaseMissing('users', ['email' => 'guest@example.com']);
    }

    public function test_password_reset_requests_need_the_challenge_too(): void
    {
        $this->enable();
        Http::fake([self::VERIFY => Http::response(['success' => false])]);

        $this->post('/forgot-password', ['email' => 'guest@example.com', 'cf-turnstile-response' => 'bad'])->assertSessionHasErrors('cf-turnstile-response');
    }

    public function test_the_guests_reply_form_and_sign_in_are_not_challenged(): void
    {
        $this->enable();
        Http::fake();

        $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'x'])->assertSessionDoesntHaveErrors('cf-turnstile-response');
        Http::assertNothingSent();
    }

    public function test_the_site_key_reaches_the_pages_only_when_enabled(): void
    {
        $this->withoutVite()->get('/register')->assertInertia(fn (AssertableInertia $page) => $page->where('turnstile', null));

        $this->enable();
        $this->withoutVite()->get('/register')->assertInertia(fn (AssertableInertia $page) => $page->where('turnstile.siteKey', 'site-key'));
    }

    public function test_the_page_policy_allows_cloudflare_only_when_enabled(): void
    {
        $this->assertStringNotContainsString('challenges.cloudflare.com', (string) $this->withoutVite()->get('/register')->headers->get('Content-Security-Policy'));

        $this->enable();
        $policy = (string) $this->withoutVite()->get('/register')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("script-src 'self' https://challenges.cloudflare.com", $policy);
        $this->assertStringContainsString('frame-src https://challenges.cloudflare.com', $policy);
    }

    private function enable(): void
    {
        config(['methaq.turnstile.site_key' => 'site-key', 'methaq.turnstile.secret_key' => 'secret-key']);
    }

    /**
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    private function registration(array $extra = []): array
    {
        return array_merge([
            'name' => 'ضيفة',
            'email' => 'guest@example.com',
            'password' => 'Str0ng-Password!',
            'password_confirmation' => 'Str0ng-Password!',
        ], $extra);
    }
}
