<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

enum CardMaterialPreset: string
{
    case Plain = 'plain';
    case Cardstock = 'cardstock';
    case Cotton = 'cotton';
    case Kraft = 'kraft';
    case Linen = 'linen';
    case Wood = 'wood';
}
