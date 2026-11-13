<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Domains\Editor\Enums\TemplateFeature;
use App\Domains\Events\Actions\GetPublicInvitationAction;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Services\EventEntitlements;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowInvitationController extends Controller
{
    public function __invoke(Request $request, GetPublicInvitationAction $getInvitation, EventEntitlements $entitlements): Response
    {
        $event = $request->attributes->get('publicEvent');
        abort_unless($event instanceof Event, 404);

        return Inertia::render('Guest/Show', [
            ...$getInvitation->execute($event),
            'rsvpEnabled' => $entitlements->allows($event, TemplateFeature::Rsvp),
        ]);
    }
}
