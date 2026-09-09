<?php

declare(strict_types=1);

namespace App\Domains\Editor\Policies;

use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;

final class TemplateAssetPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, TemplateAsset $asset): bool
    {
        return $asset->is_active || $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, TemplateAsset $asset): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, TemplateAsset $asset): bool
    {
        return false;
    }

    private function canManage(?User $user): bool
    {
        return $user?->isActive() === true && $user->role === UserRole::Admin;
    }
}
