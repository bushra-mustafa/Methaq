<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Contracts\DesignSchema;
use App\Domains\Editor\Enums\EffectId;
use InvalidArgumentException;

final readonly class EffectData
{
    public function __construct(
        public EffectId $effectId,
        public int $effectVersion,
        public bool $enabled,
        public ColorValueData $color,
        public float $intensity,
        public float $speed,
    ) {
        $capability = DesignSchema::EFFECTS[$effectId->value];
        if ($effectVersion !== $capability['version']) {
            throw new InvalidArgumentException('Unsupported effect version.');
        }
        if (! is_finite($intensity) || $intensity < $capability['intensity'][0] || $intensity > $capability['intensity'][1]) {
            throw new InvalidArgumentException('Effect intensity is outside the supported range.');
        }
        if (! is_finite($speed) || $speed < $capability['speed'][0] || $speed > $capability['speed'][1]) {
            throw new InvalidArgumentException('Effect speed is outside the supported range.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'effectId' => $this->effectId->value,
            'effectVersion' => $this->effectVersion,
            'enabled' => $this->enabled,
            'color' => $this->color->toArray(),
            'intensity' => $this->intensity,
            'speed' => $this->speed,
        ];
    }
}
