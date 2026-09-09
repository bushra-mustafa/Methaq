<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

enum MotionPolicy: string
{
    case System = 'system';
    case Reduced = 'reduced';
    case Off = 'off';
}
