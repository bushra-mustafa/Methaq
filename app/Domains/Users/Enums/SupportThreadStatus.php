<?php

declare(strict_types=1);

namespace App\Domains\Users\Enums;

use Filament\Support\Contracts\HasLabel;

enum SupportThreadStatus: string implements HasLabel
{
    case Open = 'open';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'مفتوحة',
            self::Closed => 'مغلقة',
        };
    }
}
