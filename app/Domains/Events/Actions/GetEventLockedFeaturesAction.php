<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Editor\Enums\TemplateFeature;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Services\EventEntitlements;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Gate;

/** The invitation features the event may not use, so the editor can say so before the owner picks one. */
final class GetEventLockedFeaturesAction
{
    public function __construct(private readonly EventEntitlements $entitlements) {}

    /** @return list<string> */
    public function execute(User $owner, Event $event): array
    {
        Gate::forUser($owner)->authorize('update', $event);
        $event->loadMissing('template');

        return array_map(
            static fn (TemplateFeature $feature): string => $feature->value,
            $this->entitlements->locked($event),
        );
    }
}
