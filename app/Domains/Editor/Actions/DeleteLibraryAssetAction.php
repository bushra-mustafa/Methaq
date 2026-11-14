<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\Exceptions\LibraryAssetException;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Editor\Services\AssetUsage;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * An administrator deletes an element they uploaded. Methaq's own elements are only hidden, never deleted,
 * and an element a design, template or collection still uses is refused (hide it instead).
 */
final class DeleteLibraryAssetAction
{
    public function __construct(private readonly AuditLogger $auditLogger, private readonly AssetUsage $usage) {}

    public function execute(User $admin, int $assetId): void
    {
        $files = DB::transaction(function () use ($admin, $assetId): array {
            $asset = TemplateAsset::query()->lockForUpdate()->findOrFail($assetId);
            Gate::forUser($admin)->authorize('delete', $asset);

            $license = is_array($asset->license_metadata) ? $asset->license_metadata : [];
            if (! isset($license['uploadedBy'])) {
                throw new LibraryAssetException('عناصر ميثاق الأساسية لا تُحذف. يمكنك إخفاؤها من الاختيار.');
            }
            if ($this->usage->isUsed($asset)) {
                throw new LibraryAssetException('هذا العنصر مستعمل في تصاميم أو قوالب أو مجموعات. أخفيه بدل حذفه، فتحتفظ به التصاميم القائمة.');
            }

            $metadata = is_array($asset->metadata) ? $asset->metadata : [];
            $publicFiles = array_filter([
                str_starts_with($asset->preview_path, 'storage/editor/library/') ? $asset->preview_path : null,
                is_string($metadata['publicPath'] ?? null) && str_starts_with($metadata['publicPath'], 'storage/editor/library/') ? $metadata['publicPath'] : null,
            ]);
            $original = str_starts_with($asset->original_path, 'editor/library/') ? $asset->original_path : null;

            $this->auditLogger->record(
                actor: $admin,
                action: AuditAction::TemplateAssetDeleted,
                subjectType: AuditSubjectType::TemplateAsset,
                subjectId: (int) $asset->getKey(),
                metadata: ['name' => $asset->name, 'type' => $asset->type->value],
            );
            $asset->delete();

            return ['public' => array_values($publicFiles), 'original' => $original];
        });

        foreach ($files['public'] as $path) {
            Storage::disk('public')->delete(substr($path, strlen('storage/')));
        }
        if ($files['original'] !== null) {
            Storage::disk('local')->delete($files['original']);
        }
    }
}
