<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Events\DTOs\EventData;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Gate;

final class GetEventAction
{
    public function execute(User $owner, Event $event): EventData
    {
        Gate::forUser($owner)->authorize('view', $event);
        $event->loadMissing(['template', 'design']);

        return EventData::fromModel($event);
    }
}
