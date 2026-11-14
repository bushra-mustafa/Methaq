<?php

declare(strict_types=1);

namespace App\Domains\Payments\DTOs;

use App\Domains\Editor\Enums\TemplateFeature;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Enums\PaymentRejectionReason;
use App\Domains\Payments\Models\Order;
use App\Domains\Payments\Services\LydAmount;
use App\Domains\Payments\Services\ManualPaymentEligibility;

/**
 * What the owner sees about paying for one event. Never exposes the receipt path,
 * its hash or internal order identifiers.
 */
final readonly class EventPaymentData
{
    /**
     * @param  'paid'|'pending_review'|'rejected'|'awaiting_receipt'  $state
     * @param  list<array{bankName: string|null, accountName: string|null, accountNumber: string|null, iban: string|null}>  $bankAccounts
     * @param  list<string>  $includedFeatures
     * @param  list<string>  $lockedFeatures
     * @param  array{amountLabel: string, status: string, submittedAt: string, reviewedAt: string|null}|null  $latestOrder
     */
    public function __construct(
        public string $state,
        public ?string $priceLabel,
        public array $bankAccounts,
        public array $includedFeatures,
        public array $lockedFeatures,
        public bool $canSubmit,
        public ?string $blocker,
        public ?array $latestOrder,
        public ?string $rejectionMessage = null,
    ) {}

    public static function fromEvent(Event $event, ManualPaymentEligibility $eligibility): self
    {
        $openOrder = $eligibility->openOrder($event);
        $latestOrder = Order::query()->where('event_id', $event->getKey())->latest('id')->first();
        $blocker = $eligibility->blocker($event, $openOrder);
        $priceMinor = $eligibility->priceMinor($event);
        $included = $eligibility->entitlements($event);

        $state = match (true) {
            $event->is_paid || $openOrder?->status === OrderStatus::Completed => 'paid',
            $openOrder?->status === OrderStatus::Pending => 'pending_review',
            $latestOrder?->status === OrderStatus::Failed => 'rejected',
            default => 'awaiting_receipt',
        };

        return new self(
            state: $state,
            priceLabel: $priceMinor > 0 ? LydAmount::format($priceMinor) : null,
            bankAccounts: $eligibility->bankAccounts(),
            includedFeatures: array_map(fn (TemplateFeature $feature): string => $feature->getLabel(), $included),
            lockedFeatures: array_values(array_map(
                fn (TemplateFeature $feature): string => $feature->getLabel(),
                array_filter(TemplateFeature::cases(), fn (TemplateFeature $feature): bool => ! in_array($feature, $included, true)),
            )),
            canSubmit: $blocker === null,
            blocker: $blocker,
            latestOrder: $latestOrder === null ? null : [
                'amountLabel' => LydAmount::format((int) $latestOrder->amount),
                'status' => $latestOrder->status->value,
                'submittedAt' => $latestOrder->created_at->setTimezone($event->timezone)->toIso8601String(),
                'reviewedAt' => $latestOrder->reviewed_at?->setTimezone($event->timezone)->toIso8601String(),
            ],
            rejectionMessage: $state === 'rejected' ? self::rejectionMessage($latestOrder) : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'state' => $this->state,
            'priceLabel' => $this->priceLabel,
            'bankAccounts' => $this->bankAccounts,
            'includedFeatures' => $this->includedFeatures,
            'lockedFeatures' => $this->lockedFeatures,
            'canSubmit' => $this->canSubmit,
            'blocker' => $this->blocker,
            'latestOrder' => $this->latestOrder,
            'rejectionMessage' => $this->rejectionMessage,
        ];
    }

    private static function rejectionMessage(?Order $order): string
    {
        $reason = $order?->failure_code === null ? null : PaymentRejectionReason::tryFrom($order->failure_code);

        return $reason?->ownerMessage() ?? 'لم نتمكن من اعتماد القسيمة السابقة. تأكدي من التحويل وارفعي قسيمة جديدة واضحة.';
    }
}
