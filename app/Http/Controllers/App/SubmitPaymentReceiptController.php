<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Events\Models\Event;
use App\Domains\Payments\Actions\CreateManualPaymentAction;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\SubmitPaymentReceiptRequest;
use Illuminate\Http\RedirectResponse;

final class SubmitPaymentReceiptController extends Controller
{
    public function __invoke(SubmitPaymentReceiptRequest $request, Event $event, CreateManualPaymentAction $createPayment): RedirectResponse
    {
        $owner = $request->user();
        abort_unless($owner instanceof User, 401);

        $createPayment->execute($owner, $event, $request->receipt());

        return redirect()->route('app.events.payment', $event)->with('status', 'receipt-submitted');
    }
}
