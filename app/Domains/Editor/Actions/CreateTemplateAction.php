<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Services\BlankDesignFactory;
use App\Domains\Events\Enums\EventCategory;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/** An administrator starts a new library template from the blank card. It stays hidden and unpriced until published. */
final class CreateTemplateAction
{
    public const PLACEHOLDER_PREVIEW = 'editor/previews/templates/blank-template.png';

    public function __construct(
        private readonly BlankDesignFactory $blankDesign,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(User $actor, string $name, EventCategory $category): Template
    {
        Gate::forUser($actor)->authorize('create', Template::class);

        return DB::transaction(function () use ($actor, $name, $category): Template {
            $columns = $this->blankDesign->make()->toStorageColumns();
            $template = Template::query()->forceCreate([
                'slug' => 'studio-'.Str::lower(Str::random(10)),
                'name' => trim($name),
                'category' => $category,
                'thumbnail_path' => self::PLACEHOLDER_PREVIEW,
                'default_design_json' => $columns['design_json'],
                'default_scene_json' => $columns['scene_json'],
                'default_palette_json' => $columns['palette_json'],
                'schema_version' => $columns['schema_version'],
                'design_revision' => 1,
                'is_active' => false,
                'is_featured' => false,
                'price_minor' => 0,
                'sort_order' => ((int) Template::query()->max('sort_order')) + 1,
            ]);

            $this->auditLogger->record(
                actor: $actor,
                action: AuditAction::TemplateCreated,
                subjectType: AuditSubjectType::Template,
                subjectId: (int) $template->getKey(),
                reason: null,
                metadata: ['name' => $template->name, 'category' => $category->value],
            );

            return $template;
        });
    }
}
