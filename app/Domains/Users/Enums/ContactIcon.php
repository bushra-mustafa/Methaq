<?php

declare(strict_types=1);

namespace App\Domains\Users\Enums;

use Filament\Support\Contracts\HasLabel;

/** Icon keys the site can draw; the value is stored in contact_channels.icon. */
enum ContactIcon: string implements HasLabel
{
    case Envelope = 'envelope';
    case Phone = 'phone';
    case Whatsapp = 'whatsapp';
    case Instagram = 'instagram';
    case Facebook = 'facebook';
    case Telegram = 'telegram';
    case X = 'x';
    case Tiktok = 'tiktok';
    case Snapchat = 'snapchat';
    case Youtube = 'youtube';
    case Globe = 'globe';

    public function getLabel(): string
    {
        return match ($this) {
            self::Envelope => 'بريد إلكتروني',
            self::Phone => 'هاتف',
            self::Whatsapp => 'واتساب',
            self::Instagram => 'إنستغرام',
            self::Facebook => 'فيسبوك',
            self::Telegram => 'تيليغرام',
            self::X => 'إكس (تويتر)',
            self::Tiktok => 'تيك توك',
            self::Snapchat => 'سناب شات',
            self::Youtube => 'يوتيوب',
            self::Globe => 'موقع إلكتروني',
        };
    }
}
