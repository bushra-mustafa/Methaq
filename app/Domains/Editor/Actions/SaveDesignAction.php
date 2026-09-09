<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\DTOs\DesignSnapshotData;
use App\Domains\Editor\DTOs\SaveDesignData;
use App\Domains\Editor\Exceptions\DesignRevisionConflict;
use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Services\DesignAssetReferenceValidator;
use App\Domains\Editor\Services\DesignDocumentHydrator;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

final class SaveDesignAction
{
    public function __construct(
        private readonly DesignDocumentHydrator $hydrator,
        private readonly DesignAssetReferenceValidator $assetValidator,
    ) {}

    public function execute(User $owner, Event $event, SaveDesignData $data): DesignSnapshotData
    {
        Gate::forUser($owner)->authorize('update', $event);

        return DB::transaction(function () use ($event, $data): DesignSnapshotData {
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
            $columns = $data->document->toStorageColumns();
            $nextRevision = $design->revision + 1;
            $design->forceFill([...$columns, 'revision' => $nextRevision])->save();

            return new DesignSnapshotData($data->document, $nextRevision);
        }, 3);
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
