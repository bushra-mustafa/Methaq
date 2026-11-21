<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\DTOs\DesignSnapshotData;
use App\Domains\Editor\DTOs\SaveDesignData;
use App\Domains\Editor\Services\EventDesignWriter;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * The owner saves her draft. Allowed only while the invitation is a draft (`editDesign`); the rule is checked
 * again under the event lock, so a save that races the publish request cannot slip in after it.
 */
final class SaveDesignAction
{
    public function __construct(private readonly EventDesignWriter $writer) {}

    public function execute(User $owner, Event $event, SaveDesignData $data): DesignSnapshotData
    {
        Gate::forUser($owner)->authorize('editDesign', $event);

        return DB::transaction(function () use ($owner, $event, $data): DesignSnapshotData {
            $lockedEvent = Event::query()->lockForUpdate()->findOrFail($event->getKey());
            Gate::forUser($owner)->authorize('editDesign', $lockedEvent);

            return $this->writer->write($lockedEvent, $data);
        }, 3);
    }
}
