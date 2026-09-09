<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Enums\AuditActorType;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Models\AuditLog;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class PromoteUserToAdminAction
{
    public function execute(string $email): User
    {
        return DB::transaction(function () use ($email): User {
            $user = User::query()
                ->where('email', Str::lower(trim($email)))
                ->lockForUpdate()
                ->firstOrFail();

            if ($user->status !== UserStatus::Active) {
                throw new InvalidArgumentException('A suspended user cannot be promoted.');
            }

            if ($user->role === UserRole::Admin) {
                return $user;
            }

            $user->forceFill(['role' => UserRole::Admin])->save();

            $auditLog = new AuditLog;
            $auditLog->forceFill([
                'actor_id' => null,
                'actor_type' => AuditActorType::System,
                'action' => 'user.promoted_to_admin',
                'subject_type' => 'user',
                'subject_id' => $user->getKey(),
                'reason' => 'Trusted operational command',
                'metadata' => null,
            ])->save();

            return $user->refresh();
        });
    }
}
