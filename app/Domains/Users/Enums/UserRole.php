<?php

declare(strict_types=1);

namespace App\Domains\Users\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Admin = 'admin';
}
