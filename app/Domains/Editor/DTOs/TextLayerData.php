<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Contracts\DesignSchema;
use App\Domains\Editor\Enums\Language;
use App\Domains\Editor\Enums\TextAlignment;
use App\Domains\Editor\Enums\TextDirection;
use InvalidArgumentException;

final readonly class TextLayerData
{
    public function __construct(
        public string $content,
        public Language $language,
        public TextDirection $direction,
        public TextAlignment $alignment,
        public AssetReferenceData $font,
        public float $fontSize,
        public int $fontWeight,
        public ColorValueData $fill,
    ) {
        if (mb_strlen($content) > DesignSchema::MAXIMUM_TEXT_LENGTH) {
            throw new InvalidArgumentException('Text layer content is too long.');
        }
        if (preg_match('/<\s*\/?\s*[a-z][^>]*>|javascript\s*:/i', $content) === 1) {
            throw new InvalidArgumentException('Text layer content cannot contain markup or executable URLs.');
        }
        if (! is_finite($fontSize) || $fontSize < 8 || $fontSize > 512) {
            throw new InvalidArgumentException('Font size is outside the supported range.');
        }
        if ($fontWeight < 100 || $fontWeight > 900 || $fontWeight % 100 !== 0) {
            throw new InvalidArgumentException('Font weight must be between 100 and 900 in steps of 100.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'language' => $this->language->value,
            'direction' => $this->direction->value,
            'alignment' => $this->alignment->value,
            'font' => $this->font->toArray(),
            'fontSize' => $this->fontSize,
            'fontWeight' => $this->fontWeight,
            'fill' => $this->fill->toArray(),
        ];
    }
}
