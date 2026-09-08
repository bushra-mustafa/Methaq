<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

enum AssetType: string
{
    case Background = 'background';
    case Frame = 'frame';
    case Icon = 'icon';
    case Font = 'font';
    case Decoration = 'decoration';
    case Audio = 'audio';
}
