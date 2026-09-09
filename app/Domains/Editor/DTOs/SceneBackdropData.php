<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use InvalidArgumentException;

final readonly class SceneBackdropData
{
    /** @var list<string> */
    private const PRESETS = ['inherit', 'burgundy-nebula', 'blush-cloud', 'midnight-gold', 'emerald-silk'];

    public function __construct(public string $preset)
    {
        if (! in_array($preset, self::PRESETS, true)) {
            throw new InvalidArgumentException('Unsupported scene backdrop.');
        }
    }

    /** @return array{preset: string} */
    public function toArray(): array
    {
        return ['preset' => $this->preset];
    }
}
