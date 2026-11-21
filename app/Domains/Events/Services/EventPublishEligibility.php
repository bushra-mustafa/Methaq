<?php

declare(strict_types=1);

namespace App\Domains\Events\Services;

use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Models\Order;

/**
 * The single source of the publishing rules, read by the owner's publish panel and the
 * studio's refresh panel, and re-checked under a row lock by PublishEventAction and
 * RefreshPublishedInvitationAction.
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

    /**
     * Why an administrator cannot refresh the published invitation from the saved draft now, or null when she can.
     * Only a live invitation is refreshed: a refresh never publishes a draft, reopens an expired link (checked on
     * `expires_at`, not only on the daily status) or lifts a suspension. Editing the draft stays possible in every state.
     */
    public function refreshBlocker(Event $event, ?Order $completedOrder): ?string
    {
        return match (true) {
            $event->status === EventStatus::Draft => 'الدعوة لم تُنشر بعد. تظهر تعديلاتك فيها عندما تنشرها صاحبتها.',
            $event->status === EventStatus::Expired || $event->expires_at->lessThanOrEqualTo(now()) => 'انتهت صلاحية هذه الدعوة، فلا تُحدَّث نسختها المنشورة.',
            $event->suspended_at !== null => 'هذه الدعوة موقوفة، فلا تُحدَّث نسختها المنشورة أثناء الإيقاف.',
            ! $event->is_paid || $completedOrder === null => 'لا يوجد دفع معتمد لهذه الدعوة.',
            default => null,
        };
    }
}
