<?php

declare(strict_types=1);

namespace App\Domains\Editor\Services;

use App\Domains\Editor\DTOs\DesignSnapshotData;
use App\Domains\Editor\DTOs\SaveDesignData;
use App\Domains\Editor\Exceptions\DesignRevisionConflict;
use App\Domains\Editor\Models\EventDesign;
use App\Domains\Events\Models\Event;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

/**
 * Writes a whole design document to an event's draft under one new revision. The owner's save and the
 * administrator's studio save both go through here, so revisions, 409 conflicts and asset checks never drift
 * apart. Callers authorize first and run this inside their own transaction, after locking the event row.
 */
final class EventDesignWriter
{
    public function __construct(
        private readonly DesignDocumentHydrator $hydrator,
        private readonly DesignAssetReferenceValidator $assetValidator,
    ) {}

    public function write(Event $event, SaveDesignData $data): DesignSnapshotData
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Event designs are written inside the caller\'s transaction.');
        }

        $design = EventDesign::query()
            ->where('event_id', $event->getKey())
            ->lockForUpdate()
            ->first();
        if (! $design instanceof EventDesign) {
            throw new RuntimeException('The event design is unavailable.');
        }
        if ($design->revision !== $data->expectedRevision) {
            throw new DesignRevisionConflict($this->snapshot($design));
        }

        $this->assetValidator->assertValid($data->document, $design);
        $nextRevision = $design->revision + 1;
        $design->forceFill([...$data->document->toStorageColumns(), 'revision' => $nextRevision])->save();

        return new DesignSnapshotData($data->document, $nextRevision);
    }

    private function snapshot(EventDesign $design): DesignSnapshotData
    {
        $document = $this->hydrator->hydrate([
            'schemaVersion' => $design->schema_version,
            'canvas' => $design->design_json,
            'palette' => $design->palette_json,
            'scene' => $design->scene_json,
        ]);

        return new DesignSnapshotData($document, $design->revision);
    }
}
