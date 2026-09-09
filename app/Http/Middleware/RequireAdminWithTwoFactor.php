<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireAdminWithTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->role === UserRole::Admin, 403);

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('app.profile')->withErrors([
                'two_factor' => 'فعّلي التحقق بخطوتين قبل دخول إدارة المنصة.',
            ]);
        }

        return $next($request);
    }
}
