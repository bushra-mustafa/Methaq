<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\RSVP\Models\Rsvp;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Rsvp> */
class RsvpFactory extends Factory
{
    protected $model = Rsvp::class;

    public function definition(): array
    {
        return [
            'event_id' => EventFactory::new(),
            'submission_token' => fake()->unique()->uuid(),
            'guest_name' => 'Example guest',
            'attending_status' => 'attending',
            'companions_count' => 0,
        ];
    }
}
