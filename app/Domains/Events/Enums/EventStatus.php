<?php

declare(strict_types=1);

namespace App\Domains\Events\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Expired = 'expired';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => in_array($target, [self::Published, self::Expired], true),
            self::Published => $target === self::Expired,
            self::Expired => false,
        };
    }
}
