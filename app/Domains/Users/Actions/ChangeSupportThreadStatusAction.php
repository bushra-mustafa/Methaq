<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Enums\SupportThreadStatus;
use App\Domains\Users\Models\SupportThread;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** An administrator closes a finished conversation or reopens one. Repeating the same change is a no-op. */
final class ChangeSupportThreadStatusAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(User $actor, int $threadId, SupportThreadStatus $status): SupportThread
    {
        return DB::transaction(function () use ($actor, $threadId, $status): SupportThread {
            $thread = SupportThread::query()->lockForUpdate()->findOrFail($threadId);
            Gate::forUser($actor)->authorize('manage', $thread);

            if ($thread->status === $status) {
                return $thread;
            }

            $thread->forceFill(['status' => $status])->save();

            $this->auditLogger->record(
                actor: $actor,
                action: $status === SupportThreadStatus::Closed ? AuditAction::SupportThreadClosed : AuditAction::SupportThreadReopened,
                subjectType: AuditSubjectType::SupportThread,
                subjectId: (int) $thread->getKey(),
                metadata: ['userId' => $thread->user_id],
            );

            return $thread;
        });
    }
}
