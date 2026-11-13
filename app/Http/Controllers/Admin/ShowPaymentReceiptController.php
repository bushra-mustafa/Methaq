<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Payments\Actions\CreateManualPaymentAction;
use App\Domains\Payments\Enums\PaymentGateway;
use App\Domains\Payments\Models\Order;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a private receipt to an administrator. Registered inside the Filament panel
 * (panel auth + confirmed two-factor), and the policy is re-checked on every request,
 * so no signed or public URL for the receipt ever exists.
 */
final class ShowPaymentReceiptController extends Controller
{
    public function __invoke(Request $request, Order $order): StreamedResponse
    {
        $admin = $request->user();
        abort_unless($admin instanceof User, 403);
        Gate::forUser($admin)->authorize('view', $order);

        $path = $order->receipt_path;
        $disk = Storage::disk(CreateManualPaymentAction::DISK);
        abort_unless($order->gateway === PaymentGateway::ManualTransfer && is_string($path) && $disk->exists($path), 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $disk->response(
            $path,
            "receipt-{$order->getKey()}.{$extension}",
            [
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
                'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
            ],
            $extension === 'pdf' ? 'attachment' : 'inline',
        );
    }
}
