<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Events\Models\Event;
use App\Domains\Events\Services\PublishedRenderVisibility;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** An administrator takes an invitation offline with a reason; its link answers as if it did not exist. */
final class SuspendEventAction
{
    public function __construct(private readonly AuditLogger $auditLogger, private readonly PublishedRenderVisibility $render) {}

    public function execute(User $actor, int $eventId, string $reason): Event
    {
        return DB::transaction(function () use ($actor, $eventId, $reason): Event {
            $event = Event::query()->lockForUpdate()->findOrFail($eventId);
            Gate::forUser($actor)->authorize('moderate', $event);

            if ($event->suspended_at !== null) {
                return $event;
            }

            $event->forceFill(['suspended_at' => now(), 'suspension_reason' => $reason])->save();
            $this->render->hold($event);

            $this->auditLogger->record($actor, AuditAction::EventSuspended, AuditSubjectType::Event, (int) $event->getKey(), $reason);

            return $event;
        });
    }
}
