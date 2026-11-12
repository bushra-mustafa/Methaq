<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

use Filament\Support\Contracts\HasLabel;

/** Invitation features an administrator can lock per template. */
enum TemplateFeature: string implements HasLabel
{
    case Music = 'music';
    case AnimatedBackdrop = 'animated_backdrop';
    case Rsvp = 'rsvp';
    case HdDownload = 'hd_download';

    public function getLabel(): string
    {
        return match ($this) {
            self::Music => 'الموسيقى',
            self::AnimatedBackdrop => 'الخلفيات المتحركة',
            self::Rsvp => 'تأكيد الحضور',
            self::HdDownload => 'التحميل بدقة عالية',
        };
    }
}
