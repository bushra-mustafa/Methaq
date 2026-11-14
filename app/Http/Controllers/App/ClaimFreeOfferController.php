<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Events\Models\Event;
use App\Domains\Payments\Actions\ClaimFreeOfferAction;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ClaimFreeOfferController extends Controller
{
    public function __invoke(Request $request, Event $event, ClaimFreeOfferAction $claim): RedirectResponse
    {
        $owner = $request->user();
        abort_unless($owner instanceof User, 401);

        $claim->execute($owner, $event);

        return redirect()->route('app.events.payment', $event)->with('status', 'free-offer-claimed');
    }
}
