<?php

declare(strict_types=1);

namespace App\Domains\Payments\Policies;

use App\Domains\Payments\Models\Order;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;

/** Receipt review is an active administrator's job; owners never review their own payment. */
final class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->review($user);
    }

    public function view(User $user, Order $order): bool
    {
        return $this->review($user);
    }

    public function approve(User $user, Order $order): bool
    {
        return $this->review($user);
    }

    public function reject(User $user, Order $order): bool
    {
        return $this->review($user);
    }

    private function review(User $user): bool
    {
        return $user->isActive() && $user->role === UserRole::Admin;
    }
}
