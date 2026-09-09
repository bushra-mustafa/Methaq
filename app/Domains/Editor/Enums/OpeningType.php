<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

enum OpeningType: string
{
    case Direct = 'direct';
    case Fade = 'fade';
    case Envelope = 'envelope';
}
