<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Events\Actions\PrepareInvitationAction;
use App\Domains\Events\Models\Event;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\PrepareInvitationRequest;
use Illuminate\Http\RedirectResponse;

final class PrepareInvitationController extends Controller
{
    public function __invoke(PrepareInvitationRequest $request, Event $event, PrepareInvitationAction $prepare): RedirectResponse
    {
        $prepare->execute($request->user(), $event, $request->file('render'), (int) $request->validated('expectedRevision'));

        return back()->with('status', 'invitation-prepared');
    }
}
