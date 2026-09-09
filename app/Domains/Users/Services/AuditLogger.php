<?php

declare(strict_types=1);

namespace App\Domains\Users\Services;

use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditActorType;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\AuditLog;
use App\Domains\Users\Models\User;

final class AuditLogger
{
    /** @param array{active?: bool, previousActive?: bool} $metadata */
    public function record(
        ?User $actor,
        AuditAction $action,
        AuditSubjectType $subjectType,
        int $subjectId,
        ?string $reason = null,
        array $metadata = [],
    ): AuditLog {
        $log = new AuditLog;
        $log->forceFill([
            'actor_id' => $actor?->getKey(),
            'actor_type' => $actor === null ? AuditActorType::System : AuditActorType::User,
            'action' => $action->value,
            'subject_type' => $subjectType->value,
            'subject_id' => $subjectId,
            'reason' => $reason,
            'metadata' => $metadata === [] ? null : $metadata,
        ])->save();

        return $log;
    }
}
