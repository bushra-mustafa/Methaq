<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Events\Actions\GetEventAction;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowEventController extends Controller
{
    public function __invoke(Request $request, Event $event, GetEventAction $getEvent): Response
    {
        $owner = $request->user();
        abort_unless($owner instanceof User, 401);

        return Inertia::render('App/Events/Show', [
            'event' => $getEvent->execute($owner, $event)->toArray(),
        ]);
    }
}
