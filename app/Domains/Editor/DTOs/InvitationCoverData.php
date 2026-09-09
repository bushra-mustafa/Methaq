<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use InvalidArgumentException;

final readonly class InvitationCoverData
{
    public function __construct(
        public string $heading,
        public string $names,
        public string $dateLabel,
        public string $message,
        public string $language,
        public string $decoration,
        public bool $animateText,
    ) {
        foreach (['heading' => 120, 'names' => 160, 'dateLabel' => 80, 'message' => 500] as $field => $limit) {
            if (mb_strlen($this->{$field}) > $limit || strip_tags($this->{$field}) !== $this->{$field}) {
                throw new InvalidArgumentException("Invalid cover {$field}.");
            }
        }
        if (! in_array($language, ['ar', 'en', 'mixed'], true) || ! in_array($decoration, ['none', 'floral', 'halo'], true)) {
            throw new InvalidArgumentException('Unsupported cover presentation.');
        }
    }

    /** @return array<string, string|bool> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
