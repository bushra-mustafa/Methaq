<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Users\Models\SupportMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupportMessage> */
class SupportMessageFactory extends Factory
{
    protected $model = SupportMessage::class;

    public function definition(): array
    {
        return [
            'support_thread_id' => SupportThreadFactory::new(),
            'author_id' => UserFactory::new(),
            'from_admin' => false,
            'body' => 'Example message',
        ];
    }
}
