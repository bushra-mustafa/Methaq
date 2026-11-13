<?php

declare(strict_types=1);

namespace App\Domains\Payments\Actions;

use App\Domains\Events\Models\Event;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Enums\PaymentGateway;
use App\Domains\Payments\Exceptions\PaymentReviewException;
use App\Domains\Payments\Models\Order;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * An administrator confirms the transferred money reached the account. This marks the
 * order completed and the event paid; it never publishes (the owner presses «نشر»).
 * Locks the event first, then the order, like CreateManualPaymentAction, so the two
 * cannot deadlock. Approving twice is a no-op.
 */
final class ApprovePaymentAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(User $actor, int $orderId): Order
    {
        $eventId = (int) Order::query()->findOrFail($orderId)->event_id;

        return DB::transaction(function () use ($actor, $orderId, $eventId): Order {
            $event = Event::query()->lockForUpdate()->findOrFail($eventId);
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);
            Gate::forUser($actor)->authorize('approve', $order);

            if ($order->gateway !== PaymentGateway::ManualTransfer) {
                throw new PaymentReviewException('هذا الطلب ليس تحويلاً بنكياً يدوياً.');
            }

            if ($order->status === OrderStatus::Completed) {
                return $order;
            }

            if (! $order->status->canTransitionTo(OrderStatus::Completed)) {
                throw new PaymentReviewException('لا يمكن اعتماد هذا الطلب في حالته الحالية.');
            }

            if ($event->is_paid) {
                throw new PaymentReviewException('المناسبة معتمدة الدفع بالفعل.');
            }

            $now = now();
            $order->forceFill([
                'status' => OrderStatus::Completed,
                'completed_at' => $now,
                'transaction_id' => 'manual-'.$order->getKey(),
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => $now,
            ])->save();
            $event->forceFill(['is_paid' => true, 'paid_at' => $now])->save();

            $this->auditLogger->record(
                actor: $actor,
                action: AuditAction::PaymentApproved,
                subjectType: AuditSubjectType::Order,
                subjectId: (int) $order->getKey(),
                metadata: [
                    'eventId' => (int) $event->getKey(),
                    'amount' => $order->amount,
                    'currency' => $order->currency,
                ],
            );

            return $order;
        });
    }
}
