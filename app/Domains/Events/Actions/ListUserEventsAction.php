<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Events\DTOs\EventData;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;

final class ListUserEventsAction
{
    /** @return list<array<string, mixed>> */
    public function execute(User $owner): array
    {
        return Event::query()
            ->where('user_id', $owner->getKey())
            ->with(['template', 'design'])
            ->latest('created_at')
            ->get()
            ->map(static fn (Event $event): array => EventData::fromModel($event)->toArray())
            ->all();
    }
}
