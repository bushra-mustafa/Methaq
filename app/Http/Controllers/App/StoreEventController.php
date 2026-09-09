<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Events\Actions\CreateEventAction;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\CreateEventRequest;
use Illuminate\Http\RedirectResponse;

final class StoreEventController extends Controller
{
    public function __invoke(CreateEventRequest $request, CreateEventAction $createEvent): RedirectResponse
    {
        $owner = $request->user();
        abort_unless($owner instanceof User, 401);

        $event = $createEvent->execute($owner, $request->toData());

        return redirect()->route('app.events.show', $event)->with('status', 'event-created');
    }
}
