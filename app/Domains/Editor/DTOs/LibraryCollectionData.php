<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Models\AssetCollection;
use App\Domains\Editor\Models\AssetCollectionItem;

final readonly class LibraryCollectionData
{
    /** @param list<array{asset: LibraryAssetData, placement: array<string, mixed>, sortOrder: int}> $items */
    public function __construct(
        public string $id,
        public string $slug,
        public string $name,
        public ?string $thumbnailUrl,
        public bool $isActive,
        public array $items,
    ) {}

    public static function fromModel(AssetCollection $collection): self
    {
        return new self(
            id: (string) $collection->getKey(),
            slug: $collection->slug,
            name: $collection->name,
            thumbnailUrl: $collection->thumbnail_path === null ? null : '/'.ltrim($collection->thumbnail_path, '/'),
            isActive: $collection->is_active,
            items: $collection->items->map(static fn (AssetCollectionItem $item): array => [
                'asset' => LibraryAssetData::fromModel($item->asset),
                'placement' => is_array($item->placement_json) ? $item->placement_json : [],
                'sortOrder' => $item->sort_order,
            ])->values()->all(),
        );
    }

    /** @return array{id: string, slug: string, name: string, thumbnailUrl: string|null, isActive: bool, items: list<array<string, mixed>>} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'thumbnailUrl' => $this->thumbnailUrl,
            'isActive' => $this->isActive,
            'items' => array_map(static fn (array $item): array => [
                'asset' => $item['asset']->toArray(),
                'placement' => $item['placement'],
                'sortOrder' => $item['sortOrder'],
            ], $this->items),
        ];
    }
}
