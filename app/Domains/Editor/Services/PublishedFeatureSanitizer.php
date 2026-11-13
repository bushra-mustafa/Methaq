<?php

declare(strict_types=1);

namespace App\Domains\Editor\Services;

use App\Domains\Editor\DTOs\AudioData;
use App\Domains\Editor\DTOs\EffectData;
use App\Domains\Editor\DTOs\SceneBackdropData;
use App\Domains\Editor\DTOs\SceneConfigData;
use App\Domains\Editor\Enums\TemplateFeature;

/**
 * Removes the features an event may not use from the scene that is about to be published.
 * The owner's draft is never touched; only the published snapshot is cleaned. Rebuilds
 * the readonly value objects so the result is always a valid scene.
 */
final class PublishedFeatureSanitizer
{
    /**
     * @param  list<TemplateFeature>  $locked
     * @return array{scene: SceneConfigData, removed: list<TemplateFeature>}
     */
    public function sanitize(SceneConfigData $scene, array $locked): array
    {
        $removed = [];
        $audio = $scene->audio;
        $backdrop = $scene->backdrop;
        $effects = $scene->effects;

        if (in_array(TemplateFeature::Music, $locked, true) && ($audio->enabled || $audio->asset !== null)) {
            $audio = new AudioData(false, null, $audio->volume);
            $removed[] = TemplateFeature::Music;
        }

        if (in_array(TemplateFeature::AnimatedBackdrop, $locked, true)) {
            $changed = false;

            if ($backdrop !== null && $backdrop->preset !== 'inherit') {
                $backdrop = new SceneBackdropData('inherit');
                $changed = true;
            }

            $effects = array_map(static function (EffectData $effect) use (&$changed): EffectData {
                if (! $effect->enabled) {
                    return $effect;
                }

                $changed = true;

                return new EffectData($effect->effectId, $effect->effectVersion, false, $effect->color, $effect->intensity, $effect->speed);
            }, $effects);

            if ($changed) {
                $removed[] = TemplateFeature::AnimatedBackdrop;
            }
        }

        return [
            'scene' => new SceneConfigData(
                sceneSchemaVersion: $scene->sceneSchemaVersion,
                opening: $scene->opening,
                effects: $effects,
                audio: $audio,
                motionPolicy: $scene->motionPolicy,
                cover: $scene->cover,
                backdrop: $backdrop,
            ),
            'removed' => $removed,
        ];
    }
}
