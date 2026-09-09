<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Actions\Concerns\PasswordRules;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final class CreateNewUserAction implements CreatesNewUsers
{
    use PasswordRules;

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:254', Rule::unique(User::class)],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9 ()-]{7,32}$/'],
            'password' => $this->passwordRules(),
        ])->validate();

        $user = new User;
        $user->forceFill([
            'name' => trim((string) $input['name']),
            'email' => Str::lower(trim((string) $input['email'])),
            'phone' => $this->nullablePhone($input['phone'] ?? null),
            'password' => (string) $input['password'],
            'role' => UserRole::Customer,
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    private function nullablePhone(mixed $phone): ?string
    {
        if (! is_string($phone) || trim($phone) === '') {
            return null;
        }

        return trim($phone);
    }
}
