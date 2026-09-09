<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Contracts\DesignSchema;
use InvalidArgumentException;

final readonly class PaletteData
{
    public function __construct(
        public int $schemaVersion,
        public string $background,
        public string $surface,
        public string $primaryText,
        public string $secondaryText,
        public string $accent,
        public string $effect,
    ) {
        if ($schemaVersion !== DesignSchema::PALETTE_VERSION) {
            throw new InvalidArgumentException('Unsupported palette schema version.');
        }

        foreach ([$background, $surface, $primaryText, $secondaryText, $accent, $effect] as $color) {
            if (preg_match('/^#[0-9a-f]{6}$/i', $color) !== 1) {
                throw new InvalidArgumentException('Palette colors must use #RRGGBB.');
            }
        }
    }

    /** @return array{schemaVersion: int, values: array<string, string>} */
    public function toArray(): array
    {
        return [
            'schemaVersion' => $this->schemaVersion,
            'values' => [
                'background' => $this->background,
                'surface' => $this->surface,
                'primaryText' => $this->primaryText,
                'secondaryText' => $this->secondaryText,
                'accent' => $this->accent,
                'effect' => $this->effect,
            ],
        ];
    }
}
