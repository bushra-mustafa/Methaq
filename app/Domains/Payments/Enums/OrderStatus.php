<?php

declare(strict_types=1);

namespace App\Domains\Payments\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';

    public function canTransitionTo(self $target): bool
    {
        return $this === self::Pending && in_array($target, [self::Completed, self::Failed], true);
    }
}
