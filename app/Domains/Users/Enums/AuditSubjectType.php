<?php

declare(strict_types=1);

namespace App\Domains\Users\Enums;

enum AuditSubjectType: string
{
    case User = 'user';
    case Template = 'template';
    case TemplateAsset = 'template_asset';
    case AssetCollection = 'asset_collection';
    case Order = 'order';
    case Event = 'event';
    case SupportThread = 'support_thread';
    case ContactChannel = 'contact_channel';
    case BankAccount = 'bank_account';
    case Setting = 'setting';
    case FreeOffer = 'free_offer';
}
