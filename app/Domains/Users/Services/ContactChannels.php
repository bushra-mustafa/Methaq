<?php

declare(strict_types=1);

namespace App\Domains\Users\Services;

use App\Domains\Users\Models\ContactChannel;
use Illuminate\Support\Facades\Cache;

/** Active contact channels for public pages, cached briefly and cleared whenever an administrator changes one. */
final class ContactChannels
{
    private const CACHE_KEY = 'contact-channels.v1';

    /** @return list<array{id: int, name: string, url: string, icon: string}> */
    public function active(): array
    {
        /** @var list<array{id: int, name: string, url: string, icon: string}> */
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), fn (): array => ContactChannel::query()
            ->visible()
            ->get()
            ->filter(fn (ContactChannel $channel): bool => preg_match(ContactChannel::URL_PATTERN, $channel->url) === 1)
            ->map(fn (ContactChannel $channel): array => [
                'id' => (int) $channel->getKey(),
                'name' => $channel->name,
                'url' => $channel->url,
                'icon' => $channel->icon->value,
            ])
            ->values()
            ->all());
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
