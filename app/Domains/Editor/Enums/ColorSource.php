<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

enum ColorSource: string
{
    case Palette = 'palette';
    case Literal = 'literal';
}
