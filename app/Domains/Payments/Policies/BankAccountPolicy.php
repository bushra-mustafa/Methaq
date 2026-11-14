<?php

declare(strict_types=1);

namespace App\Domains\Payments\Policies;

use App\Domains\Payments\Models\BankAccount;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;

/** Where customers send money is changed by an active administrator only. */
final class BankAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manage($user);
    }

    public function view(User $user, BankAccount $account): bool
    {
        return $this->manage($user);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, BankAccount $account): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, BankAccount $account): bool
    {
        return $this->manage($user);
    }

    private function manage(User $user): bool
    {
        return $user->isActive() && $user->role === UserRole::Admin;
    }
}
