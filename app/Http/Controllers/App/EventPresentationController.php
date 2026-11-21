<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Editor\Actions\GetEventEditorAction;
use App\Domains\Events\Actions\GetEventPublishStateAction;
use App\Domains\Events\Actions\GetLockedDesignPageAction;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EventPresentationController extends Controller
{
    public function __invoke(Request $request, Event $event, GetEventEditorAction $getEditor, GetEventPublishStateAction $getPublishState, GetLockedDesignPageAction $getLockedPage): Response
    {
        $owner = $request->user();
        abort_unless($owner instanceof User, 401);

        $locked = $getLockedPage->execute($owner, $event);
        if ($locked !== null) {
            return Inertia::render('App/Events/DesignLocked', $locked);
        }

        return Inertia::render('App/Presentation', [
            ...$getEditor->execute($owner, $event),
            'publish' => $getPublishState->execute($owner, $event),
        ]);
    }
}
