<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Enums\AssetType;
use App\Domains\Editor\Models\TemplateAsset;

final readonly class EditorAssetData
{
    /** @param array<string, mixed> $capabilities */
    public function __construct(
        public string $id,
        public string $slug,
        public string $name,
        public string $type,
        public string $previewUrl,
        public ?string $fontFamily,
        public ?string $fontUrl,
        public int $version,
        public ?int $width,
        public ?int $height,
        public array $capabilities,
    ) {}

    public static function fromModel(TemplateAsset $asset): self
    {
        $fontFamily = $asset->type === AssetType::Font && is_array($asset->metadata)
            ? ($asset->metadata['fontFamily'] ?? null)
            : null;

        return new self(
            id: (string) $asset->getKey(),
            slug: $asset->slug,
            name: $asset->name,
            type: $asset->type->value,
            previewUrl: self::previewUrl($asset),
            fontFamily: is_string($fontFamily) ? $fontFamily : null,
            fontUrl: self::fontUrl($asset),
            version: $asset->asset_version,
            width: $asset->width,
            height: $asset->height,
            capabilities: is_array($asset->capabilities) ? $asset->capabilities : [],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'type' => $this->type,
            'previewUrl' => $this->previewUrl,
            'fontFamily' => $this->fontFamily,
            'fontUrl' => $this->fontUrl,
            'version' => $this->version,
            'width' => $this->width,
            'height' => $this->height,
            'capabilities' => $this->capabilities,
        ];
    }

    private static function fontUrl(TemplateAsset $asset): ?string
    {
        if ($asset->type !== AssetType::Font) {
            return null;
        }

        return match ($asset->slug) {
            'font-amiri' => '/brand/fonts/Amiri-Regular.ttf',
            'font-pinyon' => '/brand/fonts/PinyonScript-Regular.ttf',
            'font-noto-arabic' => '/brand/fonts/NotoSansArabic.ttf',
            'font-manrope' => '/brand/fonts/Manrope.ttf',
            default => null,
        };
    }

    private static function previewUrl(TemplateAsset $asset): string
    {
        $modifiedAt = filemtime(public_path($asset->preview_path));
        $cacheVersion = is_int($modifiedAt) ? $modifiedAt : 0;

        return '/'.ltrim($asset->preview_path, '/').'?v='.$asset->asset_version.'-'.$cacheVersion;
    }
}
