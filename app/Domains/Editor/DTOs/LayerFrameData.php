<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Contracts\DesignSchema;
use InvalidArgumentException;

final readonly class LayerFrameData
{
    public function __construct(
        public float $x,
        public float $y,
        public float $width,
        public float $height,
        public float $scaleX,
        public float $scaleY,
        public float $rotation,
        public float $opacity,
    ) {
        $values = [$x, $y, $width, $height, $scaleX, $scaleY, $rotation, $opacity];
        foreach ($values as $value) {
            if (! is_finite($value)) {
                throw new InvalidArgumentException('Layer frame values must be finite.');
            }
        }

        $limit = DesignSchema::MAXIMUM_CANVAS_DIMENSION;
        if ($x < -$limit || $x > $limit || $y < -$limit || $y > $limit) {
            throw new InvalidArgumentException('Layer coordinates are outside the supported range.');
        }
        if ($width < 1 || $width > $limit || $height < 1 || $height > $limit) {
            throw new InvalidArgumentException('Layer dimensions are outside the supported range.');
        }
        if ($scaleX < 0.05 || $scaleX > 20 || $scaleY < 0.05 || $scaleY > 20) {
            throw new InvalidArgumentException('Layer scale is outside the supported range.');
        }
        if ($rotation < -360 || $rotation > 360 || $opacity < 0 || $opacity > 1) {
            throw new InvalidArgumentException('Layer rotation or opacity is outside the supported range.');
        }
    }

    /** @return array{x: float, y: float, width: float, height: float, scaleX: float, scaleY: float, rotation: float, opacity: float} */
    public function toArray(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height,
            'scaleX' => $this->scaleX,
            'scaleY' => $this->scaleY,
            'rotation' => $this->rotation,
            'opacity' => $this->opacity,
        ];
    }
}
