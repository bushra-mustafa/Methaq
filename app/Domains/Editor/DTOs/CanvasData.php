<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Contracts\DesignSchema;
use InvalidArgumentException;

final readonly class CanvasData
{
    /** @param list<LayerData> $layers */
    public function __construct(
        public int $schemaVersion,
        public int $width,
        public int $height,
        public ColorValueData $background,
        public array $layers,
    ) {
        if ($schemaVersion !== DesignSchema::CANVAS_VERSION) {
            throw new InvalidArgumentException('Unsupported canvas schema version.');
        }
        if ($width !== DesignSchema::CANVAS_WIDTH || $height !== DesignSchema::CANVAS_HEIGHT) {
            throw new InvalidArgumentException('The first Methaq canvas format is fixed at 1080x1920.');
        }
        if (count($layers) > DesignSchema::MAXIMUM_LAYERS) {
            throw new InvalidArgumentException('Canvas layer count exceeds the supported limit.');
        }

        $identifiers = [];
        foreach ($layers as $layer) {
            if (! $layer instanceof LayerData) {
                throw new InvalidArgumentException('Canvas layers must be LayerData objects.');
            }
            if (isset($identifiers[$layer->id])) {
                throw new InvalidArgumentException('Canvas layer IDs must be unique.');
            }
            $identifiers[$layer->id] = true;
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schemaVersion' => $this->schemaVersion,
            'width' => $this->width,
            'height' => $this->height,
            'background' => $this->background->toArray(),
            'layers' => array_map(static fn (LayerData $layer): array => $layer->toArray(), $this->layers),
        ];
    }
}
