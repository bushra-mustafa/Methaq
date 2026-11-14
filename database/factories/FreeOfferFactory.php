<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Payments\Models\FreeOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FreeOffer> */
class FreeOfferFactory extends Factory
{
    protected $model = FreeOffer::class;

    public function definition(): array
    {
        return [
            'title' => 'Example offer',
            'description' => null,
            'template_id' => null,
            'max_claims' => 4,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDays(3),
            'is_active' => true,
        ];
    }
}
