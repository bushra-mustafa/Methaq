<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Payments\Models\PaymentWebhook;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaymentWebhook> */
class PaymentWebhookFactory extends Factory
{
    protected $model = PaymentWebhook::class;

    public function definition(): array
    {
        return [
            'gateway' => 'stripe',
            'gateway_event_id' => fake()->unique()->uuid(),
            'event_type' => 'test.event',
            'payload' => ['test' => true],
            'received_at' => now(),
        ];
    }
}
