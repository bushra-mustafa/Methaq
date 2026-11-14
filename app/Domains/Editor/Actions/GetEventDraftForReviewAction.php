<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Services\DesignAssetReferenceValidator;
use App\Domains\Editor\Services\EditorLibrary;
use App\Domains\Events\DTOs\EventData;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

/** An administrator opens a customer's current draft, read-only. Every view is written to the audit log. */
final class GetEventDraftForReviewAction
{
    public function __construct(
        private readonly EditorLibrary $library,
        private readonly DesignAssetReferenceValidator $references,
        private readonly AuditLogger $auditLogger,
    ) {}

    /** @return array<string, mixed> */
    public function execute(User $admin, Event $event): array
    {
        Gate::forUser($admin)->authorize('reviewDraft', $event);
        $event->loadMissing(['template', 'design', 'user']);
        $design = $event->design;
        if (! $design instanceof EventDesign) {
            throw new RuntimeException('The event design is unavailable.');
        }

        $this->auditLogger->record(
            actor: $admin,
            action: AuditAction::EventDraftViewed,
            subjectType: AuditSubjectType::Event,
            subjectId: (int) $event->getKey(),
            metadata: ['ownerId' => $event->user_id, 'revision' => $design->revision],
        );

        return [
            'event' => EventData::fromModel($event)->toArray(),
            'owner' => ['name' => $event->user->name, 'email' => $event->user->email],
            'document' => [
                'schemaVersion' => $design->schema_version,
                'canvas' => $design->design_json,
                'palette' => $design->palette_json,
                'scene' => $design->scene_json,
            ],
            'revision' => $design->revision,
            'assets' => $this->library->assets($this->references->idsFromJson($design->design_json ?? [], $design->scene_json ?? [])),
        ];
    }
}
