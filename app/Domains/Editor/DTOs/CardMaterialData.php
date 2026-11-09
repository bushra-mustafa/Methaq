<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Enums\CardMaterialPreset;
use InvalidArgumentException;

final readonly class CardMaterialData
{
    public function __construct(
        public CardMaterialPreset $preset,
        public int $version,
        public float $intensity,
    ) {
        if ($version !== 1 || ! is_finite($intensity) || $intensity < 0 || $intensity > 1) {
            throw new InvalidArgumentException('Unsupported card material version or intensity.');
        }
    }

    /** @return array{preset: string, version: int, intensity: float} */
    public function toArray(): array
    {
        return ['preset' => $this->preset->value, 'version' => $this->version, 'intensity' => $this->intensity];
    }
}
