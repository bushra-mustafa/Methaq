<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Enums\LayerType;
use InvalidArgumentException;

final readonly class LayerData
{
    public function __construct(
        public string $id,
        public LayerType $type,
        public LayerFrameData $frame,
        public bool $locked,
        public bool $visible,
        public TextLayerData|ImageLayerData|ShapeLayerData $properties,
    ) {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/', $id) !== 1) {
            throw new InvalidArgumentException('Layer ID is invalid.');
        }

        $matchesType = match ($type) {
            LayerType::Text => $properties instanceof TextLayerData,
            LayerType::Image => $properties instanceof ImageLayerData,
            LayerType::Shape => $properties instanceof ShapeLayerData,
        };
        if (! $matchesType) {
            throw new InvalidArgumentException('Layer type does not match its typed properties.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_merge([
            'id' => $this->id,
            'type' => $this->type->value,
            'frame' => $this->frame->toArray(),
            'locked' => $this->locked,
            'visible' => $this->visible,
        ], $this->properties->toArray());
    }
}
