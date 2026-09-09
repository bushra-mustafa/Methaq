<?php

declare(strict_types=1);

namespace App\Domains\Payments\Enums;

enum WebhookStatus: string
{
    case Received = 'received';
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Received => $target === self::Processing,
            self::Processing => in_array($target, [self::Received, self::Processed, self::Failed], true),
            self::Failed => $target === self::Received,
            self::Processed => false,
        };
    }
}
