<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Users\Enums\ContactIcon;
use App\Domains\Users\Models\ContactChannel;
use App\Domains\Users\Services\ContactChannels;
use Illuminate\Database\Seeder;

/** The official email address. Safe to run again: it never overwrites what an administrator changed. */
class ContactChannelSeeder extends Seeder
{
    public function run(): void
    {
        ContactChannel::query()->firstOrCreate(
            ['url' => 'mailto:info@methq.ly'],
            ['name' => 'البريد الإلكتروني', 'icon' => ContactIcon::Envelope, 'is_active' => true, 'sort_order' => 0],
        );

        app(ContactChannels::class)->forget();
    }
}
