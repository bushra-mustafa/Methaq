<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Editor\Models\EventDesign;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EventDesign> */
class EventDesignFactory extends Factory
{
    protected $model = EventDesign::class;

    public function definition(): array
    {
        return [
            'event_id' => EventFactory::new(),
            'design_json' => ['schemaVersion' => 1, 'width' => 1080, 'height' => 1920, 'layers' => []],
            'scene_json' => ['opening' => ['type' => 'direct']],
            'palette_json' => ['background' => '#F5F2E3'],
        ];
    }
}
