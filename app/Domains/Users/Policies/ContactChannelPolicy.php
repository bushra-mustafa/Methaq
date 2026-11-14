<?php

declare(strict_types=1);

namespace App\Domains\Users\Policies;

use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\ContactChannel;
use App\Domains\Users\Models\User;

/** Contact channels are public content that only an active administrator manages. */
final class ContactChannelPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manage($user);
    }

    public function view(User $user, ContactChannel $channel): bool
    {
        return $this->manage($user);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, ContactChannel $channel): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, ContactChannel $channel): bool
    {
        return $this->manage($user);
    }

    private function manage(User $user): bool
    {
        return $user->isActive() && $user->role === UserRole::Admin;
    }
}
