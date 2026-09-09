<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

enum PaletteRole: string
{
    case Background = 'background';
    case Surface = 'surface';
    case PrimaryText = 'primaryText';
    case SecondaryText = 'secondaryText';
    case Accent = 'accent';
    case Effect = 'effect';
}
