<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

enum RenderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
