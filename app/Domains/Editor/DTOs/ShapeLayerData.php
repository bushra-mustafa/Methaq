<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Enums\ShapeKind;
use InvalidArgumentException;

final readonly class ShapeLayerData
{
    public function __construct(
        public ShapeKind $shapeKind,
        public ?ColorValueData $fill,
        public ?ColorValueData $stroke,
        public float $strokeWidth,
    ) {
        if (! is_finite($strokeWidth) || $strokeWidth < 0 || $strokeWidth > 64) {
            throw new InvalidArgumentException('Shape stroke width is outside the supported range.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'shapeKind' => $this->shapeKind->value,
            'fill' => $this->fill?->toArray(),
            'stroke' => $this->stroke?->toArray(),
            'strokeWidth' => $this->strokeWidth,
        ];
    }
}
