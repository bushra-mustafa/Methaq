<?php

declare(strict_types=1);

namespace App\Domains\Payments\Services;

use App\Domains\Editor\Enums\TemplateFeature;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Models\BankAccount;
use App\Domains\Payments\Models\Order;

/**
 * The single source of the bank-transfer rules: the price owed, the features the
 * payment unlocks, the receiving account and why a receipt cannot be submitted now.
 * Read by the payment page and re-checked under a row lock by the submit Action.
 */
final class ManualPaymentEligibility
{
    /**
     * The accounts customers may pay into: the ones an administrator added (active, in order), or, while there are none,
     * the single account from the server settings. In production a test-labelled account from the settings is never offered,
     * so payments stay closed until an administrator stores a real account.
     *
     * @return list<array{bankName: string|null, accountName: string|null, accountNumber: string|null, iban: string|null}>
     */
    public function bankAccounts(): array
    {
        $stored = BankAccount::query()->visible()->get();
        if ($stored->isNotEmpty()) {
            return $stored->map(fn (BankAccount $account): array => [
                'bankName' => $this->optional($account->bank_name),
                'accountName' => $this->optional($account->account_name),
                'accountNumber' => $this->optional($account->account_number),
                'iban' => $this->optional($account->iban),
            ])->values()->all();
        }

        $account = (array) config('methaq.payment.bank_transfer', []);
        $accountNumber = trim((string) ($account['account_number'] ?? ''));
        if ($accountNumber === '') {
            return [];
        }

        if (! self::mayOfferSettingsAccount($account, (string) config('app.env'))) {
            return [];
        }

        return [[
            'bankName' => $this->optional($account['bank_name'] ?? null),
            'accountName' => $this->optional($account['account_name'] ?? null),
            'accountNumber' => $accountNumber,
            'iban' => $this->optional($account['iban'] ?? null),
        ]];
    }

    public function currency(): string
    {
        return (string) config('methaq.payment.currency', 'LYD');
    }

    public function __construct(private readonly PricingSettings $pricing) {}

    /** The price in LYD minor units: the template's price, or the blank-card price for an event that started from nothing. */
    public function priceMinor(Event $event): int
    {
        return $event->template_id === null ? $this->pricing->blankCardPriceMinor() : ($event->template?->price_minor ?? 0);
    }

    /** @return list<TemplateFeature> features the template leaves unlocked for its invitations */
    public function entitlements(Event $event): array
    {
        $locked = $event->template?->locked_features?->all() ?? [];

        return array_values(array_filter(
            TemplateFeature::cases(),
            fn (TemplateFeature $feature): bool => ! in_array($feature, $locked, true),
        ));
    }

    /** The order that already covers this event: awaiting review or approved. */
    public function openOrder(Event $event, bool $lockForUpdate = false): ?Order
    {
        return Order::query()
            ->where('event_id', $event->getKey())
            ->whereIn('status', [OrderStatus::Pending->value, OrderStatus::Completed->value])
            ->when($lockForUpdate, fn ($query) => $query->lockForUpdate())
            ->latest('id')
            ->first();
    }

    /** Why this event cannot be paid for or activated now (already paid, pending, suspended, published, past), or null. Price and bank do not matter here. */
    public function stateBlocker(Event $event, ?Order $openOrder): ?string
    {
        return match (true) {
            $event->is_paid || $openOrder?->status === OrderStatus::Completed => 'تم اعتماد الدفع لهذه المناسبة.',
            $openOrder?->status === OrderStatus::Pending => 'قسيمتك قيد المراجعة، وسيظهر القرار هنا بعد التحقق من وصول المبلغ.',
            $event->suspended_at !== null => 'هذه المناسبة موقوفة حالياً ولا يمكن الدفع لها.',
            $event->status !== EventStatus::Draft => 'يمكن الدفع للمناسبات التي لم تُنشر بعد فقط.',
            $event->event_date->lessThanOrEqualTo(now()) => 'موعد المناسبة مضى، فلا يمكن الدفع لها.',
            default => null,
        };
    }

    /** Why a new receipt cannot be submitted now, or null when it can. */
    public function blocker(Event $event, ?Order $openOrder): ?string
    {
        return $this->stateBlocker($event, $openOrder) ?? match (true) {
            $this->priceMinor($event) <= 0 => 'لم يُحدَّد سعر لهذه المناسبة بعد. تواصلي معنا لإكمال الدفع.',
            $this->bankAccounts() === [] => 'بيانات الحساب البنكي غير متاحة حالياً. حاولي لاحقاً.',
            default => null,
        };
    }

    /** Arabic and English labels that mark a bank account as a test account; the local development settings ship one. */
    public static function isTestLabelled(mixed $text): bool
    {
        $value = trim((string) $text);

        return $value !== ''
            && (str_contains($value, 'تجريبي') || str_contains($value, 'تجربة') || preg_match('/\b(test|fake|demo)\b/iu', $value) === 1);
    }

    /**
     * A test-labelled account from the server settings is never offered in production.
     *
     * @param  array<string, mixed>  $account
     */
    public static function mayOfferSettingsAccount(array $account, string $environment): bool
    {
        if ($environment !== 'production') {
            return true;
        }

        return ! self::isTestLabelled($account['bank_name'] ?? null)
            && ! self::isTestLabelled($account['account_name'] ?? null);
    }

    private function optional(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
