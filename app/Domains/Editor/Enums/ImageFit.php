<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

enum ImageFit: string
{
    case Contain = 'contain';
    case Cover = 'cover';
}
