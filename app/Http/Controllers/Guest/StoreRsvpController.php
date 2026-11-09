<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Domains\Events\Models\Event;
use App\Domains\RSVP\Actions\CreateRsvpAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\RSVP\StoreRsvpRequest;
use Illuminate\Http\RedirectResponse;

final class StoreRsvpController extends Controller
{
    public function __invoke(StoreRsvpRequest $request, CreateRsvpAction $createRsvp): RedirectResponse
    {
        $event = $request->attributes->get('publicEvent');
        abort_unless($event instanceof Event, 404);

        $createRsvp->execute($event, $request->toData());

        return back()->with('success', 'وصل تأكيدك، شكراً لمشاركتنا الفرحة.');
    }
}
