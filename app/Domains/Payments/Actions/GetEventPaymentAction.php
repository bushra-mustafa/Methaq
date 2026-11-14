<?php

declare(strict_types=1);

namespace App\Domains\Payments\Actions;

use App\Domains\Events\Models\Event;
use App\Domains\Payments\DTOs\EventPaymentData;
use App\Domains\Payments\Services\FreeOffers;
use App\Domains\Payments\Services\ManualPaymentEligibility;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Gate;

final class GetEventPaymentAction
{
    public function __construct(private readonly ManualPaymentEligibility $eligibility, private readonly FreeOffers $offers) {}

    public function execute(User $owner, Event $event): EventPaymentData
    {
        Gate::forUser($owner)->authorize('view', $event);
        $event->loadMissing('template');

        return EventPaymentData::fromEvent($event, $this->eligibility, $this->offers);
    }
}
