<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Enums\OpeningType;
use InvalidArgumentException;

final readonly class OpeningData
{
    public function __construct(
        public OpeningType $type,
        public int $durationMs,
        public ?EnvelopeData $envelope = null,
    ) {
        if ($type === OpeningType::Direct && ($durationMs !== 0 || $envelope !== null)) {
            throw new InvalidArgumentException('Direct opening must have zero duration and no envelope.');
        }
        if ($type === OpeningType::Fade && ($durationMs < 100 || $durationMs > 5000 || $envelope !== null)) {
            throw new InvalidArgumentException('Fade opening configuration is invalid.');
        }
        if ($type === OpeningType::Envelope && ($durationMs < 100 || $durationMs > 5000 || $envelope === null)) {
            throw new InvalidArgumentException('Envelope opening configuration is invalid.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $opening = ['type' => $this->type->value, 'durationMs' => $this->durationMs];
        if ($this->envelope !== null) {
            $opening['envelope'] = $this->envelope->toArray();
        }

        return $opening;
    }
}
