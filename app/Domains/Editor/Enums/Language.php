<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

enum Language: string
{
    case Arabic = 'ar';
    case English = 'en';
    case Mixed = 'mixed';
}
