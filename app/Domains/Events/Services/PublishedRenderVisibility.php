<?php

declare(strict_types=1);

namespace App\Domains\Events\Services;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Events\Models\Event;
use Illuminate\Support\Facades\Storage;

/**
 * The published card image is a static file under the public disk, so the web server hands it out without asking
 * the app. When an invitation is suspended or its link has expired, the file is moved to the private disk, so
 * anyone holding the old address gets a 404; reinstating brings it back. The database path never changes.
 */
final class PublishedRenderVisibility
{
    private const PUBLIC_PREFIX = 'storage/';

    private const HELD_DIRECTORY = 'renders-held/';

    public function hold(Event $event): void
    {
        $path = $this->relativePath($event);
        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return;
        }

        $stream = Storage::disk('public')->readStream($path);
        if ($stream === null) {
            return;
        }

        Storage::disk('local')->writeStream(self::HELD_DIRECTORY.$path, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
        Storage::disk('public')->delete($path);
    }

    public function release(Event $event): void
    {
        $path = $this->relativePath($event);
        $held = $path === null ? null : self::HELD_DIRECTORY.$path;
        if ($path === null || $held === null || ! Storage::disk('local')->exists($held)) {
            return;
        }

        $stream = Storage::disk('local')->readStream($held);
        if ($stream === null) {
            return;
        }

        Storage::disk('public')->writeStream($path, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
        Storage::disk('local')->delete($held);
    }

    /** The render's path on the public disk, or null when the event was never published. */
    private function relativePath(Event $event): ?string
    {
        $design = $event->relationLoaded('design') ? $event->design : EventDesign::query()->where('event_id', $event->getKey())->first();
        $stored = $design?->final_render_path;
        if (! is_string($stored) || ! str_starts_with($stored, self::PUBLIC_PREFIX.'renders/invitations/')) {
            return null;
        }

        return substr($stored, strlen(self::PUBLIC_PREFIX));
    }
}
