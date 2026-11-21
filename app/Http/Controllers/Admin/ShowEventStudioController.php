<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Editor\Actions\GetEventStudioAction;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowEventStudioController extends Controller
{
    public function __invoke(Request $request, Event $event, GetEventStudioAction $getStudio): Response
    {
        $admin = $request->user();
        abort_unless($admin instanceof User, 401);

        return Inertia::render('App/Editor', $getStudio->execute($admin, $event));
    }
}
