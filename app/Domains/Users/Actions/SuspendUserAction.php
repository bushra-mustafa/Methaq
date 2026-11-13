<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Exceptions\ModerationException;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * An administrator suspends a customer account with a reason. The user's sessions end on their
 * next request and their invitations stop opening. Administrators cannot be suspended here.
 */
final class SuspendUserAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(User $actor, int $userId, string $reason): User
    {
        return DB::transaction(function () use ($actor, $userId, $reason): User {
            $subject = User::query()->lockForUpdate()->findOrFail($userId);
            Gate::forUser($actor)->authorize('changeStatus', $subject);

            if ($subject->role === UserRole::Admin) {
                throw new ModerationException('لا يمكن إيقاف حساب مدير من هنا.');
            }

            if ($subject->status === UserStatus::Suspended) {
                return $subject;
            }

            $subject->forceFill([
                'status' => UserStatus::Suspended,
                'suspended_at' => now(),
                'suspension_reason' => $reason,
            ])->save();

            $this->auditLogger->record($actor, AuditAction::UserSuspended, AuditSubjectType::User, (int) $subject->getKey(), $reason);

            return $subject;
        });
    }
}
