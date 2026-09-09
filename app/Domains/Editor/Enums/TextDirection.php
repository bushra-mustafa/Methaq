<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

enum TextDirection: string
{
    case RightToLeft = 'rtl';
    case LeftToRight = 'ltr';
    case Automatic = 'auto';
}
