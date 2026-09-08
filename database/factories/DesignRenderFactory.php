<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Editor\Models\DesignRender;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DesignRender> */
class DesignRenderFactory extends Factory
{
    protected $model = DesignRender::class;

    public function definition(): array
    {
        return [
            'event_design_id' => EventDesignFactory::new(),
            'revision' => 1,
            'kind' => 'preview',
            'design_snapshot' => ['schemaVersion' => 1, 'canvas' => ['layers' => []], 'scene' => [], 'palette' => []],
            'available_at' => now(),
        ];
    }
}
