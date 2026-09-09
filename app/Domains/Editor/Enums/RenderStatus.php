<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

enum RenderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => $target === self::Processing,
            self::Processing => in_array($target, [self::Pending, self::Completed, self::Failed], true),
            self::Failed => $target === self::Pending,
            self::Completed => false,
        };
    }
}
