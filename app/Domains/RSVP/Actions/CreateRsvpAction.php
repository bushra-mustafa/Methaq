<?php

declare(strict_types=1);

namespace App\Domains\RSVP\Actions;

use App\Domains\Events\Models\Event;
use App\Domains\RSVP\DTOs\CreateRsvpData;
use App\Domains\RSVP\Models\Rsvp;

final class CreateRsvpAction
{
    public function execute(Event $event, CreateRsvpData $data): Rsvp
    {
        return Rsvp::query()->updateOrCreate(
            ['event_id' => $event->getKey(), 'submission_token' => $data->submissionToken],
            [
                'guest_name' => $data->guestName,
                'guest_phone' => $data->guestPhone,
                'attending_status' => $data->attendingStatus->value,
                'companions_count' => $data->attendingStatus->value === 'declined' ? 0 : $data->companionsCount,
                'notes' => $data->notes,
            ],
        );
    }
}
