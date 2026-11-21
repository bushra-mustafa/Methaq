<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Events\Actions\RefreshPublishedInvitationAction;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\RefreshPublishedInvitationRequest;
use Illuminate\Http\RedirectResponse;

final class RefreshPublishedInvitationController extends Controller
{
    public function __invoke(RefreshPublishedInvitationRequest $request, Event $event, RefreshPublishedInvitationAction $refresh): RedirectResponse
    {
        $admin = $request->user();
        abort_unless($admin instanceof User, 401);

        $refresh->execute($admin, $event, $request->render(), $request->expectedRevision());

        return redirect()->route('app.studio.events.presentation', $event)->with('status', 'invitation-refreshed');
    }
}
