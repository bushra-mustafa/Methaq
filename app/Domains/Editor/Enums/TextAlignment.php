<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

enum TextAlignment: string
{
    case Left = 'left';
    case Center = 'center';
    case Right = 'right';
}
