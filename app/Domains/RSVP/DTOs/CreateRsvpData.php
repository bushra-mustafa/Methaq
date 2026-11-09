<?php

declare(strict_types=1);

namespace App\Domains\RSVP\DTOs;

use App\Domains\RSVP\Enums\AttendingStatus;

final readonly class CreateRsvpData
{
    public function __construct(
        public string $submissionToken,
        public string $guestName,
        public ?string $guestPhone,
        public AttendingStatus $attendingStatus,
        public int $companionsCount,
        public ?string $notes,
    ) {}
}
