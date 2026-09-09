<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Contracts\DesignSchema;
use App\Domains\Editor\Enums\MotionPolicy;
use InvalidArgumentException;

final readonly class SceneConfigData
{
    /** @param list<EffectData> $effects */
    public function __construct(
        public int $sceneSchemaVersion,
        public OpeningData $opening,
        public array $effects,
        public AudioData $audio,
        public MotionPolicy $motionPolicy,
    ) {
        if ($sceneSchemaVersion !== DesignSchema::SCENE_VERSION) {
            throw new InvalidArgumentException('Unsupported scene schema version.');
        }
        if (count($effects) > DesignSchema::MAXIMUM_EFFECTS) {
            throw new InvalidArgumentException('Scene effect count exceeds the supported limit.');
        }

        $effectIds = [];
        foreach ($effects as $effect) {
            if (! $effect instanceof EffectData) {
                throw new InvalidArgumentException('Scene effects must be EffectData objects.');
            }
            if (isset($effectIds[$effect->effectId->value])) {
                throw new InvalidArgumentException('Scene effects cannot be duplicated.');
            }
            $effectIds[$effect->effectId->value] = true;
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'sceneSchemaVersion' => $this->sceneSchemaVersion,
            'opening' => $this->opening->toArray(),
            'effects' => array_map(static fn (EffectData $effect): array => $effect->toArray(), $this->effects),
            'audio' => $this->audio->toArray(),
            'motionPolicy' => $this->motionPolicy->value,
        ];
    }
}
