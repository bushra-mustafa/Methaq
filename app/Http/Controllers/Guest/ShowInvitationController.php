<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Domains\Editor\Enums\TemplateFeature;
use App\Domains\Events\Actions\GetPublicInvitationAction;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Services\EventEntitlements;
use App\Http\Controllers\Controller;
use App\Http\Support\PageMeta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowInvitationController extends Controller
{
    public function __invoke(Request $request, GetPublicInvitationAction $getInvitation, EventEntitlements $entitlements): Response
    {
        $event = $request->attributes->get('publicEvent');
        abort_unless($event instanceof Event, 404);

        $invitation = $getInvitation->execute($event);

        return Inertia::render('Guest/Show', [
            ...$invitation,
            'meta' => PageMeta::make($event->title, 'دعوة إلى '.$event->title.' — صُنعت بميثاق.', '/', $request->getSchemeAndHttpHost().$invitation['cardUrl'], true),
            'rsvpEnabled' => $entitlements->allows($event, TemplateFeature::Rsvp),
        ]);
    }
}
