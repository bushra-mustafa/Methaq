<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Events\Services\PublishedRenderVisibility;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** An administrator brings a suspended invitation back online. Reinstating a live one is a no-op. */
final class ReinstateEventAction
{
    public function __construct(private readonly AuditLogger $auditLogger, private readonly PublishedRenderVisibility $render) {}

    public function execute(User $actor, int $eventId): Event
    {
        return DB::transaction(function () use ($actor, $eventId): Event {
            $event = Event::query()->lockForUpdate()->findOrFail($eventId);
            Gate::forUser($actor)->authorize('moderate', $event);

            if ($event->suspended_at === null) {
                return $event;
            }

            $event->forceFill(['suspended_at' => null, 'suspension_reason' => null])->save();
            // An invitation whose link has already closed stays hidden.
            if ($event->status === EventStatus::Published && $event->expires_at->greaterThan(now())) {
                $this->render->release($event);
            }

            $this->auditLogger->record($actor, AuditAction::EventReinstated, AuditSubjectType::Event, (int) $event->getKey());

            return $event;
        });
    }
}
