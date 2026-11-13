<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Events\Services\InvitationAddress;
use Illuminate\Http\Middleware\TrustProxies;
use Tests\TestCase;

final class HttpsEnforcementTest extends TestCase
{
    public function test_plain_http_is_redirected_permanently_when_https_is_forced(): void
    {
        config(['methaq.force_https' => true]);

        $this->get('http://methq.ly/templates?category=wedding')
            ->assertStatus(301)
            ->assertRedirect('https://methq.ly/templates?category=wedding');
    }

    public function test_secure_responses_carry_hsts_and_the_health_check_is_never_redirected(): void
    {
        config(['methaq.force_https' => true]);

        $this->get('https://methq.ly/about')->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        $this->get('http://methq.ly/up')->assertOk();
    }

    public function test_development_keeps_plain_http(): void
    {
        config(['methaq.force_https' => false]);

        $this->get('http://localhost/about')->assertOk()->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_invitation_links_are_https_when_forced_even_if_app_url_is_http(): void
    {
        config(['app.url' => 'http://methq.ly', 'methaq.invitation_domain' => 'methq.ly', 'methaq.force_https' => true]);

        $this->assertSame('https://sana-ali.methq.ly', InvitationAddress::url('sana-ali'));

        config(['methaq.force_https' => false]);

        $this->assertSame('http://sana-ali.methq.ly', InvitationAddress::url('sana-ali'));
    }

    public function test_behind_a_trusted_proxy_the_forwarded_scheme_prevents_a_redirect_loop(): void
    {
        config(['methaq.force_https' => true]);
        TrustProxies::at('*');

        try {
            $this->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'methq.ly'])
                ->get('http://127.0.0.1/about')
                ->assertOk()
                ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        } finally {
            TrustProxies::flushState();
        }
    }
}
