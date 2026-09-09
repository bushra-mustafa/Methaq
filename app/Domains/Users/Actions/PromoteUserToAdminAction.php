<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class PromoteUserToAdminAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

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

            $this->auditLogger->record(
                actor: null,
                action: AuditAction::UserPromotedToAdmin,
                subjectType: AuditSubjectType::User,
                subjectId: (int) $user->getKey(),
                reason: 'Trusted operational command',
            );

            return $user->refresh();
        });
    }
}
