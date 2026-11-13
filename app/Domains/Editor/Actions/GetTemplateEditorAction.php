<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Services\DesignAssetReferenceValidator;
use App\Domains\Editor\Services\EditorLibrary;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Gate;

/** The editor payload for an administrator designing a library template. */
final class GetTemplateEditorAction
{
    public function __construct(
        private readonly EditorLibrary $library,
        private readonly DesignAssetReferenceValidator $references,
    ) {}

    /** @return array<string, mixed> */
    public function execute(User $admin, Template $template): array
    {
        Gate::forUser($admin)->authorize('design', $template);
        $template->loadMissing('assets');
        $design = $template->default_design_json ?? [];
        $scene = $template->default_scene_json ?? [];

        return [
            'mode' => 'template',
            'template' => [
                'id' => $template->getKey(),
                'name' => $template->name,
                'category' => $template->category->value,
                'categoryLabel' => $template->category->label(),
                'isActive' => $template->is_active,
                'priceMinor' => $template->price_minor,
            ],
            'document' => [
                'schemaVersion' => $template->schema_version,
                'canvas' => $design,
                'palette' => $template->default_palette_json,
                'scene' => $scene,
            ],
            'revision' => $template->design_revision,
            'assets' => $this->library->assets($this->references->idsFromJson($design, $scene)),
            'collections' => $this->library->collections(),
            'recommendedAssetIds' => $template->assets->pluck('id')->map(static fn (int $id): string => (string) $id)->values()->all(),
        ];
    }
}
