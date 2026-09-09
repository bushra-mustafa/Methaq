<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Actions\Concerns\PasswordRules;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

final class UpdateUserPasswordAction implements UpdatesUserPasswords
{
    use PasswordRules;

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ])->validateWithBag('updatePassword');

        $user->forceFill(['password' => (string) $input['password']])->save();
    }
}
