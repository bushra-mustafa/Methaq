<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\Services\EventEditorPayload;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Gate;

final class GetEventEditorAction
{
    public function __construct(private readonly EventEditorPayload $payload) {}

    /** @return array<string, mixed> */
    public function execute(User $owner, Event $event): array
    {
        Gate::forUser($owner)->authorize('update', $event);

        return $this->payload->build($event);
    }
}
