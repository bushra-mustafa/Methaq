<?php

declare(strict_types=1);

namespace App\Domains\RSVP\Actions;

use App\Domains\Events\Models\Event;
use App\Domains\RSVP\DTOs\CreateRsvpData;
use App\Domains\RSVP\Exceptions\RsvpLimitReached;
use App\Domains\RSVP\Models\Rsvp;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Records a guest's reply. Sending the same form again (same token) updates the same reply, even when two
 * requests arrive together; a new token is refused once the event holds its maximum number of replies.
 */
final class CreateRsvpAction
{
    public function execute(Event $event, CreateRsvpData $data): Rsvp
    {
        $values = [
            'guest_name' => $data->guestName,
            'guest_phone' => $data->guestPhone,
            'attending_status' => $data->attendingStatus->value,
            'companions_count' => $data->attendingStatus->value === 'declined' ? 0 : $data->companionsCount,
            'notes' => $data->notes,
        ];
        $keys = ['event_id' => $event->getKey(), 'submission_token' => $data->submissionToken];

        $existing = Rsvp::query()->where($keys)->first();
        if ($existing === null && Rsvp::query()->where('event_id', $event->getKey())->count() >= (int) config('methaq.limits.max_rsvps_per_event')) {
            throw new RsvpLimitReached('بلغت هذه المناسبة الحد الأقصى من الردود.');
        }

        try {
            return Rsvp::query()->updateOrCreate($keys, $values);
        } catch (UniqueConstraintViolationException) {
            // The same token arrived twice at once; the other request created the row first.
            return Rsvp::query()->updateOrCreate($keys, $values);
        }
    }
}
