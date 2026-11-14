<?php

declare(strict_types=1);

namespace App\Domains\Users\Enums;

enum AuditAction: string
{
    case UserPromotedToAdmin = 'user.promoted_to_admin';
    case UserSuspended = 'user.suspended';
    case UserReinstated = 'user.reinstated';
    case EventSuspended = 'event.suspended';
    case EventReinstated = 'event.reinstated';
    case TemplateActivated = 'template.activated';
    case TemplateDeactivated = 'template.deactivated';
    case TemplateFeatured = 'template.featured';
    case TemplateUnfeatured = 'template.unfeatured';
    case TemplateCommerceUpdated = 'template.commerce_updated';
    case TemplateCreated = 'template.created';
    case TemplateDesigned = 'template.designed';
    case TemplateThumbnailUpdated = 'template.thumbnail_updated';
    case EventPublished = 'event.published';
    case EventDraftViewed = 'event.draft_viewed';
    case PaymentApproved = 'payment.approved';
    case PaymentRejected = 'payment.rejected';
    case TemplateAssetUploaded = 'template_asset.uploaded';
    case TemplateAssetDeleted = 'template_asset.deleted';
    case TemplateAssetActivated = 'template_asset.activated';
    case TemplateAssetDeactivated = 'template_asset.deactivated';
    case AssetCollectionActivated = 'asset_collection.activated';
    case AssetCollectionDeactivated = 'asset_collection.deactivated';
    case SupportThreadClosed = 'support_thread.closed';
    case SupportThreadReopened = 'support_thread.reopened';
    case BankAccountSaved = 'bank_account.saved';
    case BankAccountDeleted = 'bank_account.deleted';
    case ContactChannelSaved = 'contact_channel.saved';
    case ContactChannelDeleted = 'contact_channel.deleted';
}
