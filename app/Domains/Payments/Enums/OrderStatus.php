<?php

declare(strict_types=1);

namespace App\Domains\Payments\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
