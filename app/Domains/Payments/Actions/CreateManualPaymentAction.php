<?php

declare(strict_types=1);

namespace App\Domains\Payments\Actions;

use App\Domains\Events\Actions\PrepareInvitationAction;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Enums\PaymentGateway;
use App\Domains\Payments\Models\Order;
use App\Domains\Payments\Services\ManualPaymentEligibility;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\CloudMsgSettings;
use App\Domains\Users\Services\WhatsAppOutbox;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Records a bank-transfer receipt as a pending order for the owner's draft event.
 * The receipt goes to the private disk only; the amount and unlocked features are a
 * snapshot of the template at this moment. Approval happens later, by an administrator.
 */
final class CreateManualPaymentAction
{
    public const DISK = 'local';

    public const MAX_RECEIPT_KILOBYTES = 5120;

    /** Content-detected extensions accepted for a receipt. */
    public const RECEIPT_EXTENSIONS = ['jpg', 'png', 'webp', 'pdf'];

    public function __construct(private readonly ManualPaymentEligibility $eligibility, private readonly PrepareInvitationAction $prepare, private readonly WhatsAppOutbox $outbox) {}

    public function execute(User $owner, Event $event, UploadedFile $receipt, ?UploadedFile $render = null, ?int $revision = null): Order
    {
        Gate::forUser($owner)->authorize('update', $event);

        $extension = $receipt->extension();
        if (! in_array($extension, self::RECEIPT_EXTENSIONS, true) || $receipt->getSize() > self::MAX_RECEIPT_KILOBYTES * 1024) {
            throw ValidationException::withMessages(['receipt' => 'ارفعي صورة القسيمة (JPG أو PNG أو WEBP) أو ملف PDF بحجم لا يتجاوز 5 ميغابايت.']);
        }

        $hash = hash_file('sha256', (string) $receipt->getRealPath());
        $path = $receipt->storeAs('payments/receipts/'.$event->getKey(), Str::uuid()->toString().'.'.$extension, self::DISK);
        if ($hash === false || $path === false) {
            throw new RuntimeException('Unable to store the payment receipt.');
        }

        $preparedPath = null;
        try {
            return DB::transaction(function () use ($owner, $event, $path, $hash, $render, $revision, &$preparedPath): Order {
                $locked = Event::query()->with('template')->lockForUpdate()->findOrFail($event->getKey());
                Gate::forUser($owner)->authorize('update', $locked);

                $blocker = $this->eligibility->blocker($locked, $this->eligibility->openOrder($locked, lockForUpdate: true));
                if ($blocker !== null) {
                    throw ValidationException::withMessages(['receipt' => $blocker]);
                }

                $pending = Order::query()->where('user_id', $owner->getKey())->where('status', OrderStatus::Pending->value)->count();
                if ($pending >= (int) config('methaq.limits.max_pending_receipts')) {
                    throw ValidationException::withMessages(['receipt' => 'لديك عدة قسائم قيد المراجعة. انتظري قرار الإدارة عليها قبل رفع قسيمة أخرى.']);
                }

                $whatsappPhone = null;
                if (CloudMsgSettings::get('enabled')) {
                    $currentOwner = $owner->fresh();
                    if (! $this->outbox->verified($currentOwner)) {
                        throw ValidationException::withMessages(['phone' => 'أكدي رقم واتساب قبل رفع القسيمة.']);
                    }
                    if ($render === null || $revision === null) {
                        throw ValidationException::withMessages(['render' => 'انتظري تجهيز صورة الدعوة قبل رفع القسيمة.']);
                    }
                    $preparedPath = $this->prepare->execute($currentOwner, $locked, $render, $revision);
                    $whatsappPhone = $currentOwner->whatsapp_phone;
                }

                $order = new Order;
                $order->forceFill([
                    'user_id' => $owner->getKey(),
                    'event_id' => $locked->getKey(),
                    'amount' => $this->eligibility->priceMinor($locked),
                    'currency' => $this->eligibility->currency(),
                    'status' => OrderStatus::Pending,
                    'gateway' => PaymentGateway::ManualTransfer,
                    'idempotency_key' => Str::uuid()->toString(),
                    'receipt_path' => $path,
                    'receipt_sha256' => $hash,
                    'whatsapp_phone' => $whatsappPhone,
                    'entitlements' => $this->eligibility->entitlements($locked),
                ])->save();

                $this->outbox->receiptSubmitted($order);

                return $order;
            });
        } catch (Throwable $exception) {
            Storage::disk(self::DISK)->delete($path);
            if ($preparedPath !== null) {
                Storage::disk(self::DISK)->delete($preparedPath);
            }

            throw $exception;
        }
    }
}
