<?php

declare(strict_types=1);

namespace App\Domains\Payments\Actions;

use App\Domains\Events\Models\Event;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Enums\PaymentGateway;
use App\Domains\Payments\Models\FreeOffer;
use App\Domains\Payments\Models\FreeOfferClaim;
use App\Domains\Payments\Models\Order;
use App\Domains\Payments\Services\FreeOffers;
use App\Domains\Payments\Services\ManualPaymentEligibility;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The owner uses a live free offer for one of her draft events. Under row locks (event, then offer) it re-checks the
 * window, the places left and that she has not used the offer before, then records the claim and settles the event with a
 * zero-amount completed order, so entitlements and publishing behave exactly as for a paid event.
 */
final class ClaimFreeOfferAction
{
    public function __construct(
        private readonly FreeOffers $offers,
        private readonly ManualPaymentEligibility $eligibility,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(User $owner, Event $event): Order
    {
        return DB::transaction(function () use ($owner, $event): Order {
            $locked = Event::query()->with('template')->lockForUpdate()->findOrFail($event->getKey());
            Gate::forUser($owner)->authorize('update', $locked);

            $blocker = $this->eligibility->stateBlocker($locked, $this->eligibility->openOrder($locked, lockForUpdate: true));
            if ($blocker !== null) {
                throw ValidationException::withMessages(['offer' => $blocker]);
            }

            $offer = $this->offers->open($locked, lock: true);
            if (! $offer instanceof FreeOffer) {
                throw ValidationException::withMessages(['offer' => 'العرض المجاني غير متاح الآن: انتهت مدته أو اكتملت مقاعده.']);
            }

            if (FreeOfferClaim::query()->where('free_offer_id', $offer->getKey())->where('user_id', $owner->getKey())->exists()) {
                throw ValidationException::withMessages(['offer' => 'استفدتِ من هذا العرض مسبقاً، ولكل حساب استفادة واحدة.']);
            }

            $claim = new FreeOfferClaim;
            $claim->forceFill(['free_offer_id' => $offer->getKey(), 'user_id' => $owner->getKey(), 'event_id' => $locked->getKey()])->save();

            $now = now();
            $order = new Order;
            $order->forceFill([
                'user_id' => $owner->getKey(),
                'event_id' => $locked->getKey(),
                'amount' => 0,
                'currency' => $this->eligibility->currency(),
                'status' => OrderStatus::Completed,
                'gateway' => PaymentGateway::FreeOffer,
                'transaction_id' => 'free-offer-'.$claim->getKey(),
                'idempotency_key' => Str::uuid()->toString(),
                'completed_at' => $now,
                'entitlements' => $this->eligibility->entitlements($locked),
            ])->save();
            $locked->forceFill(['is_paid' => true, 'paid_at' => $now])->save();

            $this->auditLogger->record(
                actor: $owner,
                action: AuditAction::FreeOfferClaimed,
                subjectType: AuditSubjectType::FreeOffer,
                subjectId: (int) $offer->getKey(),
                metadata: ['eventId' => (int) $locked->getKey(), 'orderId' => (int) $order->getKey()],
            );

            return $order;
        });
    }
}
