<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Services\EditorLibrary;
use App\Domains\Events\DTOs\EventData;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

final class GetEventEditorAction
{
    /** @return array<string, mixed> */
    public function execute(User $owner, Event $event): array
    {
        Gate::forUser($owner)->authorize('update', $event);
        $event->loadMissing(['template.assets', 'design']);
        $design = $event->design;
        if (! $design instanceof EventDesign) {
            throw new RuntimeException('The event design is unavailable.');
        }

        $library = new EditorLibrary;
        $referencedIds = $this->referencedAssetIds($design);

        return [
            'event' => EventData::fromModel($event)->toArray(),
            'document' => [
                'schemaVersion' => $design->schema_version,
                'canvas' => $design->design_json,
                'palette' => $design->palette_json,
                'scene' => $design->scene_json,
            ],
            'revision' => $design->revision,
            'assets' => $library->assets($referencedIds),
            'collections' => $library->collections(),
            'recommendedAssetIds' => $event->template?->assets->pluck('id')->map(static fn (int $id): string => (string) $id)->values()->all() ?? [],
        ];
    }

    /** @return list<int> */
    private function referencedAssetIds(EventDesign $design): array
    {
        $identifiers = [];
        $layers = $design->design_json['layers'] ?? [];
        if (is_array($layers)) {
            foreach ($layers as $layer) {
                if (! is_array($layer)) {
                    continue;
                }
                $reference = $layer['asset'] ?? $layer['font'] ?? null;
                if (is_array($reference) && isset($reference['assetId']) && is_string($reference['assetId']) && ctype_digit($reference['assetId'])) {
                    $identifiers[] = (int) $reference['assetId'];
                }
            }
        }

        $audioReference = $design->scene_json['audio']['asset']['assetId'] ?? null;
        if (is_string($audioReference) && ctype_digit($audioReference)) {
            $identifiers[] = (int) $audioReference;
        }

        return array_values(array_unique($identifiers));
    }
}
