<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs before Filament's Authenticate on /admin: an active administrator who has not
 * confirmed two-factor authentication is sent to the profile page to set it up,
 * exactly like `admin.2fa`, instead of meeting a bare 403. Everyone else passes on.
 */
final class SendAdministratorsToTwoFactorSetup
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->isActive() && $user->role === UserRole::Admin && ! $user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('app.profile')->withErrors([
                'two_factor' => 'فعّلي التحقق بخطوتين قبل دخول إدارة المنصة.',
            ]);
        }

        return $next($request);
    }
}
