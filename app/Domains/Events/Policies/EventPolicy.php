<?php

declare(strict_types=1);

namespace App\Domains\Events\Policies;

use App\Domains\Events\Models\Event;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;

final class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Event $event): bool
    {
        return $user->isActive() && $event->user_id === $user->getKey();
    }

    public function update(User $user, Event $event): bool
    {
        return $this->view($user, $event);
    }

    public function publish(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->view($user, $event);
    }

    /** Suspending or reinstating an invitation is an administrator's call, not an owner-policy override. */
    public function moderate(User $user, Event $event): bool
    {
        return $user->isActive() && $user->role === UserRole::Admin;
    }
}
