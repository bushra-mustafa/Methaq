<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

enum ShapeKind: string
{
    case Rectangle = 'rectangle';
    case Ellipse = 'ellipse';
    case Line = 'line';
}
