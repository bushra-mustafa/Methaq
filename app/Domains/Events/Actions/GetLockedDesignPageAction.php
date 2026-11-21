<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Events\DTOs\EventData;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * After publishing the owner's editor and presentation step show a closed notice instead of the design tools:
 * only the Methaq team edits a published invitation. Returns null while the owner may still edit her draft.
 */
final class GetLockedDesignPageAction
{
    public function __construct(private readonly GetEventPublishStateAction $publishState) {}

    /** @return array{event: array<string, mixed>, publish: array<string, mixed>}|null */
    public function execute(User $owner, Event $event): ?array
    {
        Gate::forUser($owner)->authorize('update', $event);
        if (Gate::forUser($owner)->allows('editDesign', $event)) {
            return null;
        }

        $event->loadMissing(['template', 'design']);

        return [
            'event' => EventData::fromModel($event)->toArray(),
            'publish' => $this->publishState->execute($owner, $event),
        ];
    }
}
