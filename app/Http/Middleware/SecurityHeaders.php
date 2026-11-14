<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Support\TurnstileVerifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defence-in-depth response headers for every page. The Content-Security-Policy covers the public
 * and signed-in app; the Filament panel (/admin, /livewire) renders inline scripts of its own and
 * keeps only the basic headers.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
        ];
        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        // Not in local development: the Vite dev server and its hot-reload scripts cannot satisfy a strict policy.
        if (! app()->environment('local') && ! $response->headers->has('Content-Security-Policy') && ! $request->is('admin', 'admin/*', 'livewire/*', 'livewire-*/*')) {
            $response->headers->set('Content-Security-Policy', $this->policy());
        }

        return $response;
    }

    private function policy(): string
    {
        $scripts = ["'self'"];
        $connections = ["'self'"];
        $analytics = $this->origin((string) config('methaq.analytics.script'));
        if (config('methaq.analytics.enabled') && $analytics !== null) {
            $scripts[] = $analytics;
            $connections[] = $analytics;
        }

        $frames = [];
        if (app(TurnstileVerifier::class)->enabled()) {
            // The Turnstile widget: its script, its frame and its verification calls.
            $scripts[] = 'https://challenges.cloudflare.com';
            $connections[] = 'https://challenges.cloudflare.com';
            $frames[] = 'https://challenges.cloudflare.com';
        }

        $styles = ["'self'", "'unsafe-inline'"];
        $images = ["'self'", 'data:', 'blob:'];
        $fonts = ["'self'"];

        return implode('; ', [
            "default-src 'self'",
            'script-src '.implode(' ', $scripts),
            'style-src '.implode(' ', $styles),
            'img-src '.implode(' ', $images),
            'font-src '.implode(' ', $fonts),
            ...($frames === [] ? [] : ['frame-src '.implode(' ', $frames)]),
            "media-src 'self' blob:",
            'connect-src '.implode(' ', $connections),
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ]);
    }

    private function origin(string $url): ?string
    {
        $parts = parse_url($url);
        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
