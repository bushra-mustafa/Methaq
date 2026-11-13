<?php

declare(strict_types=1);

namespace App\Domains\Events\Services;

use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Models\Order;

/**
 * The single source of the publishing rules, read by the owner's publish panel and
 * re-checked under a row lock by PublishEventAction.
 */
final class EventPublishEligibility
{
    /** Why the event cannot be published now, or null when it can. */
    public function blocker(Event $event, ?Order $completedOrder): ?string
    {
        return match (true) {
            $event->status === EventStatus::Published => 'المناسبة منشورة بالفعل.',
            $event->status === EventStatus::Expired => 'انتهت صلاحية هذه المناسبة.',
            $event->suspended_at !== null => 'هذه المناسبة موقوفة حالياً ولا يمكن نشرها.',
            ! $event->is_paid || $completedOrder === null => 'لا يمكن النشر قبل اعتماد الدفع.',
            $event->event_date->lessThanOrEqualTo(now()) => 'موعد المناسبة مضى، فلا يمكن نشرها.',
            default => null,
        };
    }
}
