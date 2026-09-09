<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return Inertia::render('App/Dashboard', [
            'user' => [
                'name' => $user->name,
                'emailVerified' => $user->hasVerifiedEmail(),
            ],
        ]);
    }
}
