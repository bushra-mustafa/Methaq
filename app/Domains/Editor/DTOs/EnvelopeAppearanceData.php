<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use InvalidArgumentException;

final readonly class EnvelopeAppearanceData
{
    public function __construct(
        public string $style,
        public string $sealStyle,
        public int $sealX,
        public int $sealY,
        public int $sealSize,
    ) {
        if (! in_array($style, ['classic', 'luxury', 'minimal', 'rounded', 'gatefold'], true)
            || ! in_array($sealStyle, ['wax', 'medallion', 'methaq'], true)
            || $sealX < 20 || $sealX > 80
            || $sealY < 25 || $sealY > 75
            || $sealSize < 14 || $sealSize > 26) {
            throw new InvalidArgumentException('Envelope appearance is outside supported bounds.');
        }
    }

    /** @return array{style: string, sealStyle: string, sealX: int, sealY: int, sealSize: int} */
    public function toArray(): array
    {
        return ['style' => $this->style, 'sealStyle' => $this->sealStyle, 'sealX' => $this->sealX, 'sealY' => $this->sealY, 'sealSize' => $this->sealSize];
    }
}
