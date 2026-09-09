<?php

declare(strict_types=1);

namespace App\Domains\Events\Enums;

enum EventPresentationState: string
{
    case Draft = 'draft';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Expired = 'expired';
}
