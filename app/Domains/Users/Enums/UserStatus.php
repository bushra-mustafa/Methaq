<?php

declare(strict_types=1);

namespace App\Domains\Users\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
}
