<?php

declare(strict_types=1);

namespace App\Http\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Checks a Cloudflare Turnstile token on the server. Closed on failure: no answer from Cloudflare means no pass. */
final class TurnstileVerifier
{
    public const FIELD = 'cf-turnstile-response';

    public function enabled(): bool
    {
        return filled(config('methaq.turnstile.site_key')) && filled(config('methaq.turnstile.secret_key'));
    }

    public function siteKey(): ?string
    {
        return $this->enabled() ? (string) config('methaq.turnstile.site_key') : null;
    }

    public function passes(?string $token, ?string $ip): bool
    {
        if (! is_string($token) || $token === '' || strlen($token) > 2048) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(5)->post((string) config('methaq.turnstile.verify_url'), array_filter([
                'secret' => (string) config('methaq.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]));

            return $response->successful() && $response->json('success') === true;
        } catch (Throwable $exception) {
            Log::warning('Turnstile verification could not reach Cloudflare.', ['error' => $exception->getMessage()]);

            return false;
        }
    }
}
