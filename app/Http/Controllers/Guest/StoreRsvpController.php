<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Domains\Events\Models\Event;
use App\Domains\RSVP\Actions\CreateRsvpAction;
use App\Domains\RSVP\Exceptions\RsvpLimitReached;
use App\Http\Controllers\Controller;
use App\Http\Requests\RSVP\StoreRsvpRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

final class StoreRsvpController extends Controller
{
    public function __invoke(StoreRsvpRequest $request, CreateRsvpAction $createRsvp): RedirectResponse
    {
        $event = $request->attributes->get('publicEvent');
        abort_unless($event instanceof Event, 404);

        try {
            $createRsvp->execute($event, $request->toData());
        } catch (RsvpLimitReached $exception) {
            throw ValidationException::withMessages(['rsvp' => $exception->getMessage().' تواصلي مع أصحاب المناسبة مباشرة.']);
        }

        return back()->with('success', 'وصل تأكيدك، شكراً لمشاركتنا الفرحة.');
    }
}
