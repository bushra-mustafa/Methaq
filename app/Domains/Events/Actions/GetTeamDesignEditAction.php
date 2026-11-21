<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Events\Models\Event;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\AuditLog;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * The note on the owner's event page when the Methaq team changed her design: when it happened, and whether
 * guests see it yet. Read from the audit entries the studio writes; it never names the administrator.
 */
final class GetTeamDesignEditAction
{
    /** @return array{editedAt: string, refreshedAt: string|null, inPublishedVersion: bool}|null */
    public function execute(User $owner, Event $event): ?array
    {
        Gate::forUser($owner)->authorize('view', $event);

        $edit = $this->latest($event, AuditAction::EventDesignEditedByAdmin);
        if ($edit === null) {
            return null;
        }

        $event->loadMissing('design');
        $refresh = $this->latest($event, AuditAction::EventInvitationRefreshed);
        $editedRevision = $edit->metadata['revision'] ?? null;
        $publishedRevision = $event->design?->published_revision;
        $inPublishedVersion = is_int($editedRevision) && $publishedRevision !== null && $publishedRevision >= $editedRevision;
        $refreshedAfterEdit = $refresh !== null && $refresh->created_at->greaterThanOrEqualTo($edit->created_at);

        return [
            'editedAt' => $edit->created_at->setTimezone($event->timezone)->toIso8601String(),
            'refreshedAt' => $inPublishedVersion && $refreshedAfterEdit ? $refresh->created_at->setTimezone($event->timezone)->toIso8601String() : null,
            'inPublishedVersion' => $inPublishedVersion,
        ];
    }

    private function latest(Event $event, AuditAction $action): ?AuditLog
    {
        return AuditLog::query()
            ->where('subject_type', AuditSubjectType::Event->value)
            ->where('subject_id', $event->getKey())
            ->where('action', $action->value)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }
}
