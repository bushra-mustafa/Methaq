<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Actions\Concerns\PasswordRules;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

final class ResetUserPasswordAction implements ResetsUserPasswords
{
    use PasswordRules;

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, ['password' => $this->passwordRules()])->validate();
        $user->forceFill(['password' => (string) $input['password']])->save();
    }
}
