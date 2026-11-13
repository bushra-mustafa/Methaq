<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\DTOs\DesignSnapshotData;
use App\Domains\Editor\DTOs\SaveDesignData;
use App\Domains\Editor\Exceptions\DesignRevisionConflict;
use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Services\DesignAssetReferenceValidator;
use App\Domains\Editor\Services\DesignDocumentHydrator;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Saves a template design from the studio. Same optimistic revision rule as an owner's design,
 * but the starting point for new events changes only: events already created keep their own copy.
 */
final class SaveTemplateDesignAction
{
    public function __construct(
        private readonly DesignDocumentHydrator $hydrator,
        private readonly DesignAssetReferenceValidator $assetValidator,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(User $admin, Template $template, SaveDesignData $data): DesignSnapshotData
    {
        Gate::forUser($admin)->authorize('design', $template);

        return DB::transaction(function () use ($admin, $template, $data): DesignSnapshotData {
            $locked = Template::query()->lockForUpdate()->findOrFail($template->getKey());
            if ($locked->design_revision !== $data->expectedRevision) {
                throw new DesignRevisionConflict($this->snapshot($locked));
            }

            $this->assetValidator->assertValidAgainst(
                $data->document,
                $this->assetValidator->idsFromJson($locked->default_design_json ?? [], $locked->default_scene_json ?? []),
            );
            $columns = $data->document->toStorageColumns();
            $nextRevision = $locked->design_revision + 1;
            $locked->forceFill([
                'default_design_json' => $columns['design_json'],
                'default_scene_json' => $columns['scene_json'],
                'default_palette_json' => $columns['palette_json'],
                'schema_version' => $columns['schema_version'],
                'design_revision' => $nextRevision,
            ])->save();

            $referenced = $this->assetValidator->idsFromJson($columns['design_json'], $columns['scene_json']);
            $locked->assets()->sync(collect($referenced)->values()->mapWithKeys(static fn (int $id, int $index): array => [$id => ['sort_order' => $index]])->all());

            $this->auditLogger->record(
                actor: $admin,
                action: AuditAction::TemplateDesigned,
                subjectType: AuditSubjectType::Template,
                subjectId: (int) $locked->getKey(),
                reason: null,
                metadata: ['revision' => $nextRevision, 'layers' => count($data->document->canvas->layers)],
            );

            return new DesignSnapshotData($data->document, $nextRevision);
        }, 3);
    }

    private function snapshot(Template $template): DesignSnapshotData
    {
        $document = $this->hydrator->hydrate([
            'schemaVersion' => $template->schema_version,
            'canvas' => $template->default_design_json,
            'palette' => $template->default_palette_json,
            'scene' => $template->default_scene_json,
        ]);

        return new DesignSnapshotData($document, $template->design_revision);
    }
}
