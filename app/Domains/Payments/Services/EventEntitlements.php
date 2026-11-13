<?php

declare(strict_types=1);

namespace App\Domains\Payments\Services;

use App\Domains\Editor\Enums\TemplateFeature;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Models\Order;

/**
 * Which invitation features an event may use. The source of truth is the entitlement
 * snapshot of its completed order (the template's locks as they were when the owner paid);
 * events without a snapshot fall back to their template's current locks, and events
 * without a template are unrestricted.
 */
final class EventEntitlements
{
    public function completedOrder(Event $event): ?Order
    {
        return Order::query()
            ->where('event_id', $event->getKey())
            ->where('status', OrderStatus::Completed->value)
            ->latest('id')
            ->first();
    }

    /** @return list<TemplateFeature> */
    public function unlocked(Event $event, ?Order $completedOrder = null): array
    {
        $order = $completedOrder ?? $this->completedOrder($event);

        if ($order !== null && $order->entitlements !== null) {
            return array_values($order->entitlements->all());
        }

        $locked = $event->template?->locked_features?->all() ?? [];

        return array_values(array_filter(
            TemplateFeature::cases(),
            static fn (TemplateFeature $feature): bool => ! in_array($feature, $locked, true),
        ));
    }

    public function allows(Event $event, TemplateFeature $feature): bool
    {
        return in_array($feature, $this->unlocked($event), true);
    }

    /** @return list<TemplateFeature> */
    public function locked(Event $event, ?Order $completedOrder = null): array
    {
        $unlocked = $this->unlocked($event, $completedOrder);

        return array_values(array_filter(
            TemplateFeature::cases(),
            static fn (TemplateFeature $feature): bool => ! in_array($feature, $unlocked, true),
        ));
    }
}
