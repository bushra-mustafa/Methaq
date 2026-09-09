<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Models\TemplateAsset;

final readonly class LibraryAssetData
{
    /** @param array<string, mixed> $capabilities */
    public function __construct(
        public string $id,
        public string $slug,
        public string $name,
        public string $type,
        public string $previewUrl,
        public int $version,
        public bool $isActive,
        public array $capabilities,
    ) {}

    public static function fromModel(TemplateAsset $asset): self
    {
        return new self(
            id: (string) $asset->getKey(),
            slug: $asset->slug,
            name: $asset->name,
            type: $asset->type->value,
            previewUrl: self::previewUrl($asset->preview_path),
            version: $asset->asset_version,
            isActive: $asset->is_active,
            capabilities: is_array($asset->capabilities) ? $asset->capabilities : [],
        );
    }

    /** @return array{id: string, slug: string, name: string, type: string, previewUrl: string, version: int, isActive: bool, capabilities: array<string, mixed>} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'type' => $this->type,
            'previewUrl' => $this->previewUrl,
            'version' => $this->version,
            'isActive' => $this->isActive,
            'capabilities' => $this->capabilities,
        ];
    }

    private static function previewUrl(string $path): string
    {
        return '/'.ltrim($path, '/');
    }
}
