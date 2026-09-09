<?php

declare(strict_types=1);

namespace App\Domains\Users\Enums;

enum AuditAction: string
{
    case UserPromotedToAdmin = 'user.promoted_to_admin';
    case TemplateActivated = 'template.activated';
    case TemplateDeactivated = 'template.deactivated';
    case TemplateAssetActivated = 'template_asset.activated';
    case TemplateAssetDeactivated = 'template_asset.deactivated';
    case AssetCollectionActivated = 'asset_collection.activated';
    case AssetCollectionDeactivated = 'asset_collection.deactivated';
}
