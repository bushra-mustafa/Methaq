<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\DTOs\DesignSnapshotData;
use App\Domains\Editor\DTOs\SaveDesignData;
use App\Domains\Editor\Services\EventDesignWriter;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * An administrator saves a customer's draft from the studio, in any invitation state. It shares the owner's
 * revision, so a stale tab on either side gets the usual 409. Guests keep the published snapshot until the
 * administrator refreshes it (RefreshPublishedInvitationAction). Every save is audited without design content.
 */
final class SaveEventDesignAsAdministratorAction
{
    public function __construct(
        private readonly EventDesignWriter $writer,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(User $admin, Event $event, SaveDesignData $data): DesignSnapshotData
    {
        Gate::forUser($admin)->authorize('administerDesign', $event);

        return DB::transaction(function () use ($admin, $event, $data): DesignSnapshotData {
            $lockedEvent = Event::query()->lockForUpdate()->findOrFail($event->getKey());
            $snapshot = $this->writer->write($lockedEvent, $data);

            $this->auditLogger->record(
                actor: $admin,
                action: AuditAction::EventDesignEditedByAdmin,
                subjectType: AuditSubjectType::Event,
                subjectId: (int) $lockedEvent->getKey(),
                metadata: [
                    'ownerId' => $lockedEvent->user_id,
                    'revision' => $snapshot->revision,
                    'status' => $lockedEvent->status->value,
                ],
            );

            return $snapshot;
        }, 3);
    }
}
