<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Events\Models\Event;
use App\Domains\Payments\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'event_id' => EventFactory::new(),
            'user_id' => fn (array $attributes): int => Event::query()->findOrFail($attributes['event_id'])->user_id,
            'amount' => 1000,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'idempotency_key' => fake()->unique()->uuid(),
        ];
    }
}
