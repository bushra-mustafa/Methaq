<?php

declare(strict_types=1);

namespace App\Domains\Events\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Expired = 'expired';
}
