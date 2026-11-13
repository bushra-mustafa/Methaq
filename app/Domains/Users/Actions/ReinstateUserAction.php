<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** An administrator lifts a suspension. Reinstating an active account is a no-op. */
final class ReinstateUserAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(User $actor, int $userId): User
    {
        return DB::transaction(function () use ($actor, $userId): User {
            $subject = User::query()->lockForUpdate()->findOrFail($userId);
            Gate::forUser($actor)->authorize('changeStatus', $subject);

            if ($subject->status === UserStatus::Active) {
                return $subject;
            }

            $subject->forceFill([
                'status' => UserStatus::Active,
                'suspended_at' => null,
                'suspension_reason' => null,
            ])->save();

            $this->auditLogger->record($actor, AuditAction::UserReinstated, AuditSubjectType::User, (int) $subject->getKey());

            return $subject;
        });
    }
}
