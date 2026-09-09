<?php

declare(strict_types=1);

namespace App\Domains\Editor\Policies;

use App\Domains\Editor\Models\Template;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;

final class TemplatePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Template $template): bool
    {
        return $template->is_active || ($user?->isActive() === true && $user->role === UserRole::Admin);
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->role === UserRole::Admin;
    }

    public function update(User $user, Template $template): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Template $template): bool
    {
        return $this->create($user);
    }
}
