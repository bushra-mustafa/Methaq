<?php

declare(strict_types=1);

namespace App\Domains\RSVP\Actions;

use App\Domains\Events\Models\Event;
use App\Domains\RSVP\Enums\AttendingStatus;
use App\Domains\RSVP\Models\Rsvp;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * The host's view of the replies to one event: totals and the replies themselves. Owner only
 * (an administrator is not a blanket override). Guest phone numbers and notes appear here and nowhere else.
 */
final class ListEventRsvpsAction
{
    /** Replies shown on the page; the CSV export carries all of them. */
    public const PAGE_LIMIT = 500;

    /**
     * @return array{summary: array{responses: int, attending: int, companions: int, headcount: int, declined: int, maybe: int}, rsvps: list<array<string, mixed>>, truncated: bool}
     */
    public function execute(User $owner, Event $event): array
    {
        Gate::forUser($owner)->authorize('update', $event);

        $rows = Rsvp::query()->where('event_id', $event->getKey())->latest('id')->limit(self::PAGE_LIMIT + 1)->get();
        $truncated = $rows->count() > self::PAGE_LIMIT;

        return [
            'summary' => $this->summary($event),
            'rsvps' => $rows->take(self::PAGE_LIMIT)->map(static fn (Rsvp $rsvp): array => [
                'id' => (string) $rsvp->getKey(),
                'guestName' => $rsvp->guest_name,
                'guestPhone' => $rsvp->guest_phone,
                'status' => $rsvp->attending_status->value,
                'companions' => $rsvp->companions_count,
                'notes' => $rsvp->notes,
                'submittedAt' => $rsvp->updated_at?->toIso8601String(),
            ])->values()->all(),
            'truncated' => $truncated,
        ];
    }

    /** @return array{responses: int, attending: int, companions: int, headcount: int, declined: int, maybe: int} */
    public function summary(Event $event): array
    {
        $counts = Rsvp::query()
            ->where('event_id', $event->getKey())
            ->selectRaw('attending_status, count(*) as replies, coalesce(sum(companions_count), 0) as companions')
            ->groupBy('attending_status')
            ->get()
            ->keyBy(static fn (Rsvp $row): string => $row->attending_status->value);

        $attending = (int) ($counts->get(AttendingStatus::Attending->value)?->getAttribute('replies') ?? 0);
        $companions = (int) ($counts->get(AttendingStatus::Attending->value)?->getAttribute('companions') ?? 0);

        return [
            'responses' => (int) $counts->sum(static fn (Rsvp $row): int => (int) $row->getAttribute('replies')),
            'attending' => $attending,
            'companions' => $companions,
            'headcount' => $attending + $companions,
            'declined' => (int) ($counts->get(AttendingStatus::Declined->value)?->getAttribute('replies') ?? 0),
            'maybe' => (int) ($counts->get(AttendingStatus::Maybe->value)?->getAttribute('replies') ?? 0),
        ];
    }
}
