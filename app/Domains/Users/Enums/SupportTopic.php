<?php

declare(strict_types=1);

namespace App\Domains\Users\Enums;

use Filament\Support\Contracts\HasLabel;

/** What a customer writes to the team about. */
enum SupportTopic: string implements HasLabel
{
    case Problem = 'problem';
    case CustomDesign = 'custom_design';

    public function getLabel(): string
    {
        return match ($this) {
            self::Problem => 'عرض مشكلة',
            self::CustomDesign => 'طلب تصميم مخصص',
        };
    }
}
