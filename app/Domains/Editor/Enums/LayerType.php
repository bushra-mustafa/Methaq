<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

enum LayerType: string
{
    case Text = 'text';
    case Image = 'image';
    case Shape = 'shape';
}
