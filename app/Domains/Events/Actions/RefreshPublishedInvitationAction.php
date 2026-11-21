<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Services\InvitationPng;
use App\Domains\Events\Models\Event;
use App\Domains\Events\Services\EventPublishEligibility;
use App\Domains\Events\Services\PublishedSnapshotWriter;
use App\Domains\Payments\Services\EventEntitlements;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * An administrator replaces a live invitation's published copy with the saved draft. The browser sends the card
 * PNG of the current revision; the snapshot writer shared with the owner's publish removes the features the event
 * may not use and stores the image. Status, publish date and expiry stay as they were, and the previous card file
 * is deleted once the new one is committed. Guests see the change on their next visit.
 */
final class RefreshPublishedInvitationAction
{
    public function __construct(
        private readonly EventPublishEligibility $eligibility,
        private readonly EventEntitlements $entitlements,
        private readonly PublishedSnapshotWriter $snapshots,
        private readonly InvitationPng $png,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(User $admin, Event $event, UploadedFile $render, int $expectedRevision): Event
    {
        Gate::forUser($admin)->authorize('administerDesign', $event);

        $storedPath = null;
        $previousPath = null;

        try {
            $refreshed = DB::transaction(function () use ($admin, $event, $render, $expectedRevision, &$storedPath, &$previousPath): Event {
                $lockedEvent = Event::query()->with('template')->lockForUpdate()->findOrFail($event->getKey());
                $order = $this->entitlements->completedOrder($lockedEvent);
                $blocker = $this->eligibility->refreshBlocker($lockedEvent, $order);
                if ($blocker !== null) {
                    throw ValidationException::withMessages(['publish' => $blocker]);
                }

                $design = EventDesign::query()->where('event_id', $lockedEvent->getKey())->lockForUpdate()->first();
                if (! $design instanceof EventDesign) {
                    throw new RuntimeException('The event design is unavailable.');
                }
                if ($design->revision !== $expectedRevision) {
                    throw ValidationException::withMessages(['publish' => 'تغيّر التصميم منذ تحميل الصفحة. حدّثي الصفحة ثم حاولي مرة أخرى.']);
                }

                $previousRevision = $design->published_revision;
                $previousPath = $design->final_render_path;
                $snapshot = $this->snapshots->prepare($lockedEvent, $design, $order);
                $storedPath = $this->snapshots->store($design, $snapshot, $this->png->normalise($render), now());

                $this->auditLogger->record(
                    actor: $admin,
                    action: AuditAction::EventInvitationRefreshed,
                    subjectType: AuditSubjectType::Event,
                    subjectId: (int) $lockedEvent->getKey(),
                    metadata: [
                        'ownerId' => $lockedEvent->user_id,
                        'revision' => $design->revision,
                        'previousRevision' => $previousRevision,
                        ...$snapshot->auditMetadata(),
                    ],
                );

                return $lockedEvent;
            }, 3);
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk(PublishedSnapshotWriter::DISK)->delete($storedPath);
            }

            throw $exception;
        }

        $this->deletePreviousRender($refreshed, $previousPath, $storedPath);

        return $refreshed;
    }

    /** Removes the replaced card from the public disk, so the old design is not left reachable at its address. */
    private function deletePreviousRender(Event $event, ?string $previousPath, ?string $storedPath): void
    {
        $prefix = 'storage/renders/invitations/'.$event->getKey().'/';
        if ($previousPath === null || $storedPath === null || ! str_starts_with($previousPath, $prefix) || str_contains($previousPath, '..') || $previousPath === 'storage/'.$storedPath) {
            return;
        }

        Storage::disk(PublishedSnapshotWriter::DISK)->delete(substr($previousPath, strlen('storage/')));
    }
}
