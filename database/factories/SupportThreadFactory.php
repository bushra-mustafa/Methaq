<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Users\Enums\SupportThreadStatus;
use App\Domains\Users\Enums\SupportTopic;
use App\Domains\Users\Models\SupportThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupportThread> */
class SupportThreadFactory extends Factory
{
    protected $model = SupportThread::class;

    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'topic' => SupportTopic::Problem,
            'subject' => 'Example subject',
            'status' => SupportThreadStatus::Open,
            'admin_unread' => true,
            'customer_unread' => false,
            'last_message_at' => now(),
        ];
    }
}
