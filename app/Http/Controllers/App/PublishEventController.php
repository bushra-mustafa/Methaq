<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Events\Actions\PublishEventAction;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\PublishEventRequest;
use Illuminate\Http\RedirectResponse;

final class PublishEventController extends Controller
{
    public function __invoke(PublishEventRequest $request, Event $event, PublishEventAction $publishEvent): RedirectResponse
    {
        $owner = $request->user();
        abort_unless($owner instanceof User, 401);

        $publishEvent->execute($owner, $event, $request->render(), $request->expectedRevision());

        return redirect()->route('app.events.show', $event)->with('status', 'event-published');
    }
}
