<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Contracts\DesignSchema;
use InvalidArgumentException;

final readonly class DesignDocumentData
{
    public function __construct(
        public int $schemaVersion,
        public CanvasData $canvas,
        public PaletteData $palette,
        public SceneConfigData $scene,
    ) {
        if ($schemaVersion !== DesignSchema::VERSION) {
            throw new InvalidArgumentException('Unsupported design document schema version.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schemaVersion' => $this->schemaVersion,
            'canvas' => $this->canvas->toArray(),
            'palette' => $this->palette->toArray(),
            'scene' => $this->scene->toArray(),
        ];
    }

    /** @return array{design_json: array<string, mixed>, palette_json: array<string, mixed>, scene_json: array<string, mixed>, schema_version: int} */
    public function toStorageColumns(): array
    {
        return [
            'design_json' => $this->canvas->toArray(),
            'palette_json' => $this->palette->toArray(),
            'scene_json' => $this->scene->toArray(),
            'schema_version' => $this->schemaVersion,
        ];
    }
}
