<?php

declare(strict_types=1);

namespace App\Domains\Users\Policies;

use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\SupportThread;
use App\Domains\Users\Models\User;

/** A customer sees and answers only her own conversations; an active administrator handles all of them. */
final class SupportThreadPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function view(User $user, SupportThread $thread): bool
    {
        return $user->isActive() && ($this->isAdministrator($user) || $thread->user_id === $user->getKey());
    }

    public function reply(User $user, SupportThread $thread): bool
    {
        return $this->view($user, $thread);
    }

    public function manage(User $user, SupportThread $thread): bool
    {
        return $this->isAdministrator($user);
    }

    private function isAdministrator(User $user): bool
    {
        return $user->isActive() && $user->role === UserRole::Admin;
    }
}
