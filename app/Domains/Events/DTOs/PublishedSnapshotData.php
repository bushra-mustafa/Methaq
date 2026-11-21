<?php

declare(strict_types=1);

namespace App\Domains\Events\DTOs;

use App\Domains\Editor\DTOs\SceneConfigData;
use App\Domains\Editor\Enums\TemplateFeature;

/** The scene guests will receive, and which features were taken out of it on the way. */
final readonly class PublishedSnapshotData
{
    /**
     * @param  list<TemplateFeature>  $removed  features the draft uses but this event may not publish
     * @param  list<TemplateFeature>  $locked  every feature this event may not use
     */
    public function __construct(
        public SceneConfigData $scene,
        public array $removed,
        public array $locked,
    ) {}

    /** @return array{removedFeatures: list<string>, lockedFeatures: list<string>} */
    public function auditMetadata(): array
    {
        return [
            'removedFeatures' => array_map(static fn (TemplateFeature $feature): string => $feature->value, $this->removed),
            'lockedFeatures' => array_map(static fn (TemplateFeature $feature): string => $feature->value, $this->locked),
        ];
    }
}
