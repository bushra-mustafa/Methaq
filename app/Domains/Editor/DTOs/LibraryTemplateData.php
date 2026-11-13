<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Editor\Services\ThumbnailVariants;

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
        public ?string $thumbnailWebpUrl,
        public ?string $thumbnailAvifUrl,
        public array $palette,
        public bool $isActive,
        public bool $isFeatured,
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
            thumbnailWebpUrl: ThumbnailVariants::webpUrl($template->thumbnail_path),
            thumbnailAvifUrl: ThumbnailVariants::avifUrl($template->thumbnail_path),
            palette: is_array($palette) ? array_filter($palette, 'is_string') : [],
            isActive: $template->is_active,
            isFeatured: $template->is_featured,
            assets: $template->assets->map(
                static fn (TemplateAsset $asset): LibraryAssetData => LibraryAssetData::fromModel($asset),
            )->values()->all(),
        );
    }

    /** @return array{id: string, slug: string, name: string, category: string, thumbnailUrl: string, thumbnailWebpUrl: string|null, thumbnailAvifUrl: string|null, palette: array<string, string>, isActive: bool, isFeatured: bool, assets: list<array<string, mixed>>} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => $this->category,
            'thumbnailUrl' => $this->thumbnailUrl,
            'thumbnailWebpUrl' => $this->thumbnailWebpUrl,
            'thumbnailAvifUrl' => $this->thumbnailAvifUrl,
            'palette' => $this->palette,
            'isActive' => $this->isActive,
            'isFeatured' => $this->isFeatured,
            'assets' => array_map(static fn (LibraryAssetData $asset): array => $asset->toArray(), $this->assets),
        ];
    }
}
