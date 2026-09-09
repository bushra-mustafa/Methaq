<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use InvalidArgumentException;

final readonly class AudioData
{
    public function __construct(
        public bool $enabled,
        public ?AssetReferenceData $asset,
        public float $volume,
    ) {
        if ($enabled && $asset === null) {
            throw new InvalidArgumentException('Enabled audio requires an asset.');
        }
        if (! is_finite($volume) || $volume < 0 || $volume > 1) {
            throw new InvalidArgumentException('Audio volume is outside the supported range.');
        }
    }

    /** @return array{enabled: bool, asset: array{assetId: string, version: int}|null, volume: float} */
    public function toArray(): array
    {
        return ['enabled' => $this->enabled, 'asset' => $this->asset?->toArray(), 'volume' => $this->volume];
    }
}
