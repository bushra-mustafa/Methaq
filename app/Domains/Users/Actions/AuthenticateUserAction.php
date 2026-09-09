<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class AuthenticateUserAction
{
    public function __invoke(Request $request): ?User
    {
        $email = Str::lower($request->string('email')->toString());
        $user = User::query()->where('email', $email)->first();

        if ($user === null || $user->status !== UserStatus::Active || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return null;
        }

        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => $request->string('password')->toString()])->save();
        }

        return $user;
    }
}
