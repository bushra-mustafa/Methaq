<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class SecurityHeadersTest extends TestCase
{
    public function test_public_pages_carry_the_basic_headers_and_a_strict_policy(): void
    {
        $response = $this->withoutVite()->get('/login');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $this->assertStringContainsString('camera=()', (string) $response->headers->get('Permissions-Policy'));

        $policy = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("frame-ancestors 'self'", $policy);
        $this->assertStringContainsString("form-action 'self'", $policy);
        $this->assertMatchesRegularExpression("/script-src 'self'(?:;| https:)/", $policy);
    }

    public function test_the_analytics_origin_is_allowed_only_when_analytics_is_on(): void
    {
        config(['methaq.analytics.enabled' => false]);
        $this->assertStringNotContainsString('plausible.io', (string) $this->withoutVite()->get('/login')->headers->get('Content-Security-Policy'));

        config(['methaq.analytics.enabled' => true, 'methaq.analytics.script' => 'https://plausible.io/js/script.js']);
        $this->assertStringContainsString('script-src \'self\' https://plausible.io', (string) $this->withoutVite()->get('/login')->headers->get('Content-Security-Policy'));
    }

    public function test_the_admin_panel_keeps_the_basic_headers_without_the_page_policy(): void
    {
        $response = $this->get('/admin');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertNull($response->headers->get('Content-Security-Policy'));
    }
}
