<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Users\Actions\ListUserSessionsAction;
use App\Domains\Users\DTOs\UserSessionData;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ProfileController extends Controller
{
    public function __invoke(Request $request, ListUserSessionsAction $listUserSessions): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return Inertia::render('App/Profile', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'emailVerified' => $user->hasVerifiedEmail(),
            ],
            'sessions' => array_map(
                static fn (UserSessionData $session): array => $session->toArray(),
                $listUserSessions->execute($user, $request->session()->getId()),
            ),
            'twoFactor' => [
                'enabled' => $user->hasEnabledTwoFactorAuthentication(),
                'pendingConfirmation' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null,
            ],
            'status' => $request->session()->get('status'),
        ]);
    }
}
