<?php

declare(strict_types=1);

namespace App\Domains\Events\Enums;

enum EventCategory: string
{
    case Wedding = 'wedding';
    case Henna = 'henna';
    case MarriageContract = 'marriage_contract';
    case Graduation = 'graduation';
}
