<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Enums\ImageFit;

final readonly class ImageLayerData
{
    public function __construct(
        public AssetReferenceData $asset,
        public ImageFit $fit,
    ) {}

    /** @return array{asset: array{assetId: string, version: int}, fit: string} */
    public function toArray(): array
    {
        return ['asset' => $this->asset->toArray(), 'fit' => $this->fit->value];
    }
}
