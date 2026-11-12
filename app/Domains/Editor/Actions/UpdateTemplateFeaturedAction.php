<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\Models\Template;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Adds a library template to, or removes it from, the home page's featured section. */
final class UpdateTemplateFeaturedAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(User $actor, int $templateId, bool $isFeatured, string $reason): void
    {
        DB::transaction(function () use ($actor, $templateId, $isFeatured, $reason): void {
            $template = Template::query()->lockForUpdate()->findOrFail($templateId);
            Gate::forUser($actor)->authorize('update', $template);

            if ($template->is_featured === $isFeatured) {
                return;
            }

            $template->forceFill(['is_featured' => $isFeatured])->save();
            $this->auditLogger->record(
                actor: $actor,
                action: $isFeatured ? AuditAction::TemplateFeatured : AuditAction::TemplateUnfeatured,
                subjectType: AuditSubjectType::Template,
                subjectId: (int) $template->getKey(),
                reason: trim($reason),
                metadata: ['previousFeatured' => ! $isFeatured, 'featured' => $isFeatured],
            );
        });
    }
}
