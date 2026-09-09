<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions\Concerns;

use Illuminate\Validation\Rules\Password;

trait PasswordRules
{
    /** @return array<int, mixed> */
    private function passwordRules(): array
    {
        return ['required', 'string', Password::min(12)->mixedCase()->numbers()->symbols(), 'confirmed'];
    }
}
