<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Users\Enums\ContactIcon;
use App\Domains\Users\Models\ContactChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContactChannel> */
class ContactChannelFactory extends Factory
{
    protected $model = ContactChannel::class;

    public function definition(): array
    {
        return [
            'name' => 'Example channel',
            'url' => 'https://example.com/'.fake()->unique()->slug(2),
            'icon' => ContactIcon::Globe,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
