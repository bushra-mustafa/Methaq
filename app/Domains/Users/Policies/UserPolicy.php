<?php

declare(strict_types=1);

namespace App\Domains\Users\Policies;

use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;

final class UserPolicy
{
    public function view(User $actor, User $subject): bool
    {
        return $actor->isActive() && ($actor->is($subject) || $actor->role === UserRole::Admin);
    }

    public function update(User $actor, User $subject): bool
    {
        return $actor->isActive() && $actor->is($subject);
    }

    public function changeStatus(User $actor, User $subject): bool
    {
        return $actor->isActive() && $actor->role === UserRole::Admin && ! $actor->is($subject);
    }
}
