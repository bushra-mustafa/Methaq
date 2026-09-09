<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\Enums\LibraryResource;
use App\Domains\Editor\Models\AssetCollection;
use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class UpdateLibraryItemStatusAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(User $actor, LibraryResource $resource, int $id, bool $isActive, string $reason): void
    {
        DB::transaction(function () use ($actor, $resource, $id, $isActive, $reason): void {
            $item = $this->lockedItem($resource, $id);
            Gate::forUser($actor)->authorize('update', $item);

            $previousActive = (bool) $item->getAttribute('is_active');
            if ($previousActive === $isActive) {
                return;
            }

            $item->forceFill(['is_active' => $isActive])->save();
            $this->auditLogger->record(
                actor: $actor,
                action: $this->auditAction($resource, $isActive),
                subjectType: $this->subjectType($resource),
                subjectId: (int) $item->getKey(),
                reason: trim($reason),
                metadata: ['previousActive' => $previousActive, 'active' => $isActive],
            );
        });
    }

    private function lockedItem(LibraryResource $resource, int $id): Model
    {
        return match ($resource) {
            LibraryResource::Template => Template::query()->lockForUpdate()->findOrFail($id),
            LibraryResource::Asset => TemplateAsset::query()->lockForUpdate()->findOrFail($id),
            LibraryResource::Collection => AssetCollection::query()->lockForUpdate()->findOrFail($id),
        };
    }

    private function auditAction(LibraryResource $resource, bool $isActive): AuditAction
    {
        return match ([$resource, $isActive]) {
            [LibraryResource::Template, true] => AuditAction::TemplateActivated,
            [LibraryResource::Template, false] => AuditAction::TemplateDeactivated,
            [LibraryResource::Asset, true] => AuditAction::TemplateAssetActivated,
            [LibraryResource::Asset, false] => AuditAction::TemplateAssetDeactivated,
            [LibraryResource::Collection, true] => AuditAction::AssetCollectionActivated,
            [LibraryResource::Collection, false] => AuditAction::AssetCollectionDeactivated,
        };
    }

    private function subjectType(LibraryResource $resource): AuditSubjectType
    {
        return match ($resource) {
            LibraryResource::Template => AuditSubjectType::Template,
            LibraryResource::Asset => AuditSubjectType::TemplateAsset,
            LibraryResource::Collection => AuditSubjectType::AssetCollection,
        };
    }
}
