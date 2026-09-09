<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Contracts\DesignSchema;
use App\Domains\Editor\Enums\EnvelopePresetId;
use InvalidArgumentException;

final readonly class EnvelopeData
{
    public function __construct(
        public EnvelopePresetId $presetId,
        public int $presetVersion,
        public ColorValueData $paperColor,
        public ColorValueData $liningColor,
        public ColorValueData $sealColor,
        public string $monogram,
    ) {
        $capability = DesignSchema::ENVELOPES[$presetId->value];
        if ($presetVersion !== $capability['version']) {
            throw new InvalidArgumentException('Unsupported envelope preset version.');
        }
        if (mb_strlen($monogram) > $capability['maximum_monogram_length']) {
            throw new InvalidArgumentException('Envelope monogram is too long.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'presetId' => $this->presetId->value,
            'presetVersion' => $this->presetVersion,
            'paperColor' => $this->paperColor->toArray(),
            'liningColor' => $this->liningColor->toArray(),
            'sealColor' => $this->sealColor->toArray(),
            'monogram' => $this->monogram,
        ];
    }
}
