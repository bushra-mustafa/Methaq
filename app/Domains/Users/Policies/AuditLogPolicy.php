<?php

declare(strict_types=1);

namespace App\Domains\Users\Policies;

use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\AuditLog;
use App\Domains\Users\Models\User;

/** The audit trail is read by active administrators only and never changed. */
final class AuditLogPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isActive() && $actor->role === UserRole::Admin;
    }

    public function view(User $actor, AuditLog $log): bool
    {
        return $this->viewAny($actor);
    }
}
