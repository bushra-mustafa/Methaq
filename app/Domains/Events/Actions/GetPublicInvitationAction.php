<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Editor\DTOs\EditorAssetData;
use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Events\Models\Event;
use RuntimeException;

final class GetPublicInvitationAction
{
    /** @return array<string, mixed> */
    public function execute(Event $event): array
    {
        $event->loadMissing('design');
        $design = $event->design;
        if (! $design instanceof EventDesign) {
            throw new RuntimeException('The invitation design is unavailable.');
        }

        if ($design->final_render_path === null || $design->published_scene_json === null || $design->published_palette_json === null) {
            throw new RuntimeException('The published invitation render is unavailable.');
        }

        return [
            'event' => [
                'title' => $event->title,
                'eventDate' => $event->event_date->setTimezone($event->timezone)->format('Y-m-d H:i'),
                'timezone' => $event->timezone,
            ],
            'cardUrl' => '/'.ltrim($design->final_render_path, '/'),
            'palette' => $design->published_palette_json,
            'scene' => $design->published_scene_json,
            'assets' => TemplateAsset::query()
                ->where('is_active', true)
                ->orderBy('type')
                ->orderBy('id')
                ->get()
                ->map(static fn (TemplateAsset $asset): array => EditorAssetData::fromModel($asset)->toArray())
                ->values()
                ->all(),
        ];
    }
}
