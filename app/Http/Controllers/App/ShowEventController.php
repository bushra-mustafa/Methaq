<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Events\Actions\GetEventAction;
use App\Domains\Events\Actions\GetInvitationProgressAction;
use App\Domains\Events\Models\Event;
use App\Domains\Events\Services\VisitStats;
use App\Domains\Payments\Actions\GetEventPaymentAction;
use App\Domains\RSVP\Actions\ListEventRsvpsAction;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowEventController extends Controller
{
    public function __invoke(Request $request, Event $event, GetEventAction $getEvent, GetEventPaymentAction $getPayment, ListEventRsvpsAction $rsvps, VisitStats $visits, GetInvitationProgressAction $getProgress): Response
    {
        $owner = $request->user();
        abort_unless($owner instanceof User, 401);

        $payment = $getPayment->execute($owner, $event);

        return Inertia::render('App/Events/Show', [
            'event' => $getEvent->execute($owner, $event)->toArray(),
            'payment' => $payment->toArray(),
            'progress' => $getProgress->execute($owner, $event, $payment),
            'rsvpSummary' => $rsvps->summary($event),
            'visits' => $visits->forEvent((int) $event->getKey()),
        ]);
    }
}
