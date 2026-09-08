<?php

declare(strict_types=1);

namespace App\Domains\RSVP\Enums;

enum AttendingStatus: string
{
    case Attending = 'attending';
    case Declined = 'declined';
    case Maybe = 'maybe';
}
