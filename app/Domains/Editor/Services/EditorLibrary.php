<?php

declare(strict_types=1);

namespace App\Domains\Editor\Services;

use App\Domains\Editor\DTOs\EditorAssetData;
use App\Domains\Editor\Models\AssetCollection;
use App\Domains\Editor\Models\AssetCollectionItem;
use App\Domains\Editor\Models\TemplateAsset;
use Illuminate\Database\Eloquent\Builder;

/** The asset and collection payload the editor receives, shared by the owner's editor and the template studio. */
final class EditorLibrary
{
    /**
     * @param  list<int>  $referencedIds  Assets the design already uses, kept even if later deactivated.
     * @return list<array<string, mixed>>
     */
    public function assets(array $referencedIds): array
    {
        return TemplateAsset::query()
            ->where(static fn (Builder $query): Builder => $query
                ->where('is_active', true)
                ->when($referencedIds !== [], static fn (Builder $assetQuery): Builder => $assetQuery->orWhereIn('id', $referencedIds)))
            ->orderBy('type')
            ->orderBy('id')
            ->get()
            ->map(static fn (TemplateAsset $asset): array => EditorAssetData::fromModel($asset)->toArray())
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public function collections(): array
    {
        return AssetCollection::query()
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
    }
}
