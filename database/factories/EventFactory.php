<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Events\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Event> */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'template_id' => null,
            'category' => 'wedding',
            'title' => 'Example invitation',
            'subdomain' => 'event-'.fake()->unique()->uuid(),
            'event_date' => now()->addMonth(),
            'timezone' => 'Africa/Tripoli',
            'expires_at' => fn (array $attributes): CarbonImmutable => CarbonImmutable::parse($attributes['event_date'])->addDays(10),
        ];
    }
}
