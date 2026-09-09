<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

enum LibraryResource: string
{
    case Template = 'template';
    case Asset = 'asset';
    case Collection = 'collection';
}
