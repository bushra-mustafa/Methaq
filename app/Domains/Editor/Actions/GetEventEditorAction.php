<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\DTOs\EditorAssetData;
use App\Domains\Editor\Models\AssetCollection;
use App\Domains\Editor\Models\AssetCollectionItem;
use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Events\DTOs\EventData;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
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

        $referencedIds = $this->referencedAssetIds($design);
        $assets = TemplateAsset::query()
            ->where(static fn (Builder $query): Builder => $query
                ->where('is_active', true)
                ->when($referencedIds !== [], static fn (Builder $assetQuery): Builder => $assetQuery->orWhereIn('id', $referencedIds)))
            ->orderBy('type')
            ->orderBy('id')
            ->get();

        $collections = AssetCollection::query()
            ->where('is_active', true)
            ->with(['items' => static fn ($query) => $query
                ->whereHas('asset', static fn ($assetQuery) => $assetQuery->where('is_active', true))
                ->with('asset')
                ->orderBy('sort_order')])
            ->orderBy('id')
            ->get()
            ->filter(static fn (AssetCollection $collection): bool => $collection->items->isNotEmpty())
            ->map(static fn (AssetCollection $collection): array => [
                'id' => (string) $collection->getKey(),
                'slug' => $collection->slug,
                'name' => $collection->name,
                'thumbnailUrl' => $collection->thumbnail_path === null ? null : '/'.ltrim($collection->thumbnail_path, '/'),
                'items' => $collection->items->map(static fn (AssetCollectionItem $item): array => [
                    'assetId' => (string) $item->template_asset_id,
                    'placement' => $item->placement_json,
                    'sortOrder' => $item->sort_order,
                ])->values()->all(),
            ])->values()->all();

        return [
            'event' => EventData::fromModel($event)->toArray(),
            'document' => [
                'schemaVersion' => $design->schema_version,
                'canvas' => $design->design_json,
                'palette' => $design->palette_json,
                'scene' => $design->scene_json,
            ],
            'revision' => $design->revision,
            'assets' => $assets->map(static fn (TemplateAsset $asset): array => EditorAssetData::fromModel($asset)->toArray())->values()->all(),
            'collections' => $collections,
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
