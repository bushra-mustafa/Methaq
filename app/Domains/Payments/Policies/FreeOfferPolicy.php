<?php

declare(strict_types=1);

namespace App\Domains\Payments\Policies;

use App\Domains\Payments\Models\FreeOffer;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;

/** Free offers give the service away, so only an active administrator creates or changes them. */
final class FreeOfferPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manage($user);
    }

    public function view(User $user, FreeOffer $offer): bool
    {
        return $this->manage($user);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, FreeOffer $offer): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, FreeOffer $offer): bool
    {
        return $this->manage($user);
    }

    private function manage(User $user): bool
    {
        return $user->isActive() && $user->role === UserRole::Admin;
    }
}
