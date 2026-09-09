<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Enums\ColorSource;
use App\Domains\Editor\Enums\PaletteRole;
use InvalidArgumentException;

final readonly class ColorValueData
{
    public function __construct(
        public ColorSource $source,
        public ?PaletteRole $role,
        public ?string $value,
    ) {
        $validPalette = $source === ColorSource::Palette && $role !== null && $value === null;
        $validLiteral = $source === ColorSource::Literal && $role === null && $value !== null && preg_match('/^#[0-9a-f]{6}$/i', $value) === 1;

        if (! $validPalette && ! $validLiteral) {
            throw new InvalidArgumentException('Color must reference one palette role or contain one #RRGGBB literal.');
        }
    }

    public static function palette(PaletteRole $role): self
    {
        return new self(ColorSource::Palette, $role, null);
    }

    public static function literal(string $value): self
    {
        return new self(ColorSource::Literal, null, $value);
    }

    /** @return array{source: string, role: string}|array{source: string, value: string} */
    public function toArray(): array
    {
        if ($this->source === ColorSource::Palette) {
            return ['source' => $this->source->value, 'role' => $this->role->value];
        }

        return ['source' => $this->source->value, 'value' => $this->value];
    }
}
