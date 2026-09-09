<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Users\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next, string $guard = 'web'): Response
    {
        $auth = Auth::guard($guard);
        $user = $auth->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if ($user->status === UserStatus::Active) {
            return $next($request);
        }

        $auth->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => 'هذا الحساب غير متاح حالياً.',
        ]);
    }
}
