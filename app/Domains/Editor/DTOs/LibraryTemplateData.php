<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Models\TemplateAsset;

final readonly class LibraryTemplateData
{
    /**
     * @param  array<string, string>  $palette
     * @param  list<LibraryAssetData>  $assets
     */
    public function __construct(
        public string $id,
        public string $slug,
        public string $name,
        public string $category,
        public string $thumbnailUrl,
        public array $palette,
        public bool $isActive,
        public array $assets,
    ) {}

    public static function fromModel(Template $template): self
    {
        $palette = is_array($template->default_palette_json)
            ? ($template->default_palette_json['values'] ?? [])
            : [];

        return new self(
            id: (string) $template->getKey(),
            slug: $template->slug,
            name: $template->name,
            category: $template->category->value,
            thumbnailUrl: '/'.ltrim($template->thumbnail_path, '/'),
            palette: is_array($palette) ? array_filter($palette, 'is_string') : [],
            isActive: $template->is_active,
            assets: $template->assets->map(
                static fn (TemplateAsset $asset): LibraryAssetData => LibraryAssetData::fromModel($asset),
            )->values()->all(),
        );
    }

    /** @return array{id: string, slug: string, name: string, category: string, thumbnailUrl: string, palette: array<string, string>, isActive: bool, assets: list<array<string, mixed>>} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => $this->category,
            'thumbnailUrl' => $this->thumbnailUrl,
            'palette' => $this->palette,
            'isActive' => $this->isActive,
            'assets' => array_map(static fn (LibraryAssetData $asset): array => $asset->toArray(), $this->assets),
        ];
    }
}
