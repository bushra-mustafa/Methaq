<?php

declare(strict_types=1);

namespace App\Domains\Users\Enums;

enum AuditActorType: string
{
    case User = 'user';
    case System = 'system';
}
