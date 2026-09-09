<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Users\Actions\LogoutOtherSessionsAction;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\LogoutOtherSessionsRequest;
use Illuminate\Http\RedirectResponse;

final class LogoutOtherSessionsController extends Controller
{
    public function __invoke(LogoutOtherSessionsRequest $request, LogoutOtherSessionsAction $logoutOtherSessions): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $logoutOtherSessions->execute(
            $user,
            $request->string('password')->toString(),
            $request->session()->getId(),
        );

        return back()->with('status', 'other-sessions-logged-out');
    }
}
