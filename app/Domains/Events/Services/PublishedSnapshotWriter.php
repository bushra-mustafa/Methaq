<?php

declare(strict_types=1);

namespace App\Domains\Events\Services;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Services\DesignDocumentHydrator;
use App\Domains\Editor\Services\PublishedFeatureSanitizer;
use App\Domains\Events\DTOs\PublishedSnapshotData;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Models\Order;
use App\Domains\Payments\Services\EventEntitlements;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Builds the guest-facing copy of a saved design: the scene with locked features removed, the palette, the
 * revision and the card PNG on the public disk. The owner's first publish and the administrator's refresh both
 * use it, so neither path can skip the entitlement locks. Callers hold the event and design rows locked.
 */
final class PublishedSnapshotWriter
{
    public const DISK = 'public';

    public function __construct(
        private readonly DesignDocumentHydrator $hydrator,
        private readonly PublishedFeatureSanitizer $sanitizer,
        private readonly EventEntitlements $entitlements,
    ) {}

    public function prepare(Event $event, EventDesign $design, ?Order $completedOrder): PublishedSnapshotData
    {
        try {
            $document = $this->hydrator->hydrate([
                'schemaVersion' => $design->schema_version,
                'canvas' => $design->design_json,
                'palette' => $design->palette_json,
                'scene' => $design->scene_json,
            ]);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['publish' => 'التصميم يحتاج مراجعة قبل النشر.']);
        }

        $locked = $this->entitlements->locked($event, $completedOrder);
        ['scene' => $scene, 'removed' => $removed] = $this->sanitizer->sanitize($document->scene, $locked);

        return new PublishedSnapshotData($scene, $removed, $locked);
    }

    /**
     * Stores the card and points the published columns at it. Returns the new path on the public disk; the caller
     * deletes it if a later step of its transaction fails. The draft columns are never touched.
     */
    public function store(EventDesign $design, PublishedSnapshotData $snapshot, string $pngBytes, CarbonInterface $renderedAt): string
    {
        $relativePath = 'renders/invitations/'.$design->event_id.'/'.Str::uuid()->toString().'.png';
        if (! Storage::disk(self::DISK)->put($relativePath, $pngBytes)) {
            throw new RuntimeException('Unable to store the published invitation.');
        }

        try {
            $design->forceFill([
                'published_revision' => $design->revision,
                'published_scene_json' => $snapshot->scene->toArray(),
                'published_palette_json' => $design->palette_json,
                'final_render_path' => 'storage/'.$relativePath,
                'rendered_at' => $renderedAt,
            ])->save();
        } catch (Throwable $exception) {
            Storage::disk(self::DISK)->delete($relativePath);

            throw $exception;
        }

        return $relativePath;
    }
}
