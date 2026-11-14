<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Support\TurnstileVerifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * A light guard for the forms anyone on the internet can post: sign-up, password reset and the
 * guests' reply. A hidden «website» field that only a bot fills in is refused, and each address may
 * post a limited number of times a minute. Sign-in and two-factor have their own limiters.
 */
final class GuardPublicForms
{
    /** @var array<string, int> Path => attempts per minute for one address. */
    private const LIMITS = [
        'register' => 5,
        'forgot-password' => 5,
        'reset-password' => 10,
        'rsvp' => 10,
    ];

    /** Forms that also need a passed Turnstile challenge when it is configured. The guests' reply stays light. */
    private const TURNSTILE_PATHS = ['register', 'forgot-password'];

    public function handle(Request $request, Closure $next): Response
    {
        $path = trim($request->path(), '/');

        if (! $request->isMethod('POST') || ! isset(self::LIMITS[$path])) {
            return $next($request);
        }

        $key = 'public-form:'.$path.':'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::LIMITS[$path])) {
            abort(429, 'محاولات كثيرة خلال وقت قصير. انتظري دقيقة ثم حاولي مجدداً.');
        }

        RateLimiter::hit($key, 60);

        if (filled($request->input('website'))) {
            throw ValidationException::withMessages(['website' => 'تعذّر إرسال النموذج. حدّثي الصفحة وحاولي مرة أخرى.']);
        }

        $turnstile = app(TurnstileVerifier::class);
        if (in_array($path, self::TURNSTILE_PATHS, true) && $turnstile->enabled() && ! $turnstile->passes($request->input(TurnstileVerifier::FIELD), $request->ip())) {
            throw ValidationException::withMessages([TurnstileVerifier::FIELD => 'تعذّر التحقق من أنك لست روبوتاً. حدّثي الصفحة وحاولي مرة أخرى.']);
        }

        return $next($request);
    }
}
