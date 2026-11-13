<?php

declare(strict_types=1);

namespace App\Domains\Payments\Actions;

use App\Domains\Events\Models\Event;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Enums\PaymentGateway;
use App\Domains\Payments\Enums\PaymentRejectionReason;
use App\Domains\Payments\Exceptions\PaymentReviewException;
use App\Domains\Payments\Models\Order;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * An administrator rejects a receipt. The order fails with a reason the owner sees and the
 * event stays unpaid, so a new receipt can be uploaded. Rejecting twice is a no-op.
 */
final class RejectPaymentAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(User $actor, int $orderId, PaymentRejectionReason $reason): Order
    {
        $eventId = (int) Order::query()->findOrFail($orderId)->event_id;

        return DB::transaction(function () use ($actor, $orderId, $eventId, $reason): Order {
            Event::query()->lockForUpdate()->findOrFail($eventId);
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);
            Gate::forUser($actor)->authorize('reject', $order);

            if ($order->gateway !== PaymentGateway::ManualTransfer) {
                throw new PaymentReviewException('هذا الطلب ليس تحويلاً بنكياً يدوياً.');
            }

            if ($order->status === OrderStatus::Failed) {
                return $order;
            }

            if (! $order->status->canTransitionTo(OrderStatus::Failed)) {
                throw new PaymentReviewException('لا يمكن رفض طلب اعتُمد دفعه.');
            }

            $order->forceFill([
                'status' => OrderStatus::Failed,
                'failure_code' => $reason->value,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now(),
            ])->save();

            $this->auditLogger->record(
                actor: $actor,
                action: AuditAction::PaymentRejected,
                subjectType: AuditSubjectType::Order,
                subjectId: (int) $order->getKey(),
                reason: $reason->getLabel(),
                metadata: ['eventId' => $eventId, 'code' => $reason->value],
            );

            return $order;
        });
    }
}
