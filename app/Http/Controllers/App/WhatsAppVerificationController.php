<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Users\Actions\VerifyWhatsAppAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\WhatsAppVerificationRequest;
use Illuminate\Http\RedirectResponse;

final class WhatsAppVerificationController extends Controller
{
    public function send(WhatsAppVerificationRequest $request, VerifyWhatsAppAction $verify): RedirectResponse
    {
        $verify->send($request->user(), $request->validated('phone'));

        return back()->with('status', 'whatsapp-code-queued');
    }

    public function confirm(WhatsAppVerificationRequest $request, VerifyWhatsAppAction $verify): RedirectResponse
    {
        $verify->confirm($request->user(), $request->validated('code'));

        return back()->with('status', 'whatsapp-verified');
    }
}
