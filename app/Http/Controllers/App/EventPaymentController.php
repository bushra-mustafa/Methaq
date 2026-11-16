<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Events\Actions\GetEventAction;
use App\Domains\Events\Actions\GetInvitationProgressAction;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Actions\GetEventPaymentAction;
use App\Domains\Payments\Actions\GetPaymentPreviewAction;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\CloudMsgSettings;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EventPaymentController extends Controller
{
    public function __invoke(Request $request, Event $event, GetEventAction $getEvent, GetEventPaymentAction $getPayment, GetInvitationProgressAction $getProgress, GetPaymentPreviewAction $getPreview): Response
    {
        $owner = $request->user();
        abort_unless($owner instanceof User, 401);

        $payment = $getPayment->execute($owner, $event);

        return Inertia::render('App/Events/Payment', [
            'event' => $getEvent->execute($owner, $event)->toArray(),
            'payment' => $payment->toArray(),
            'progress' => $getProgress->execute($owner, $event, $payment),
            'whatsapp' => [
                'enabled' => (bool) CloudMsgSettings::get('enabled'),
                'phone' => $owner->whatsapp_pending_phone ?? ($owner->whatsapp_verified_at !== null ? $owner->whatsapp_phone : $owner->phone) ?? '',
                'verified' => $owner->whatsapp_verified_at !== null,
                'prepared' => $event->design?->prepared_revision !== null && $event->design->prepared_revision === $event->design->revision,
            ],
            'preparation' => $getPreview->execute($owner, $event),
        ]);
    }
}
