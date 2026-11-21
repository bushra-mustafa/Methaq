<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\Enums\TemplateFeature;
use App\Domains\Editor\Services\EventEditorPayload;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Services\EventEntitlements;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\Gate;

/**
 * An administrator opens a customer's draft in the studio (editor or presentation step) to change it. Opening it
 * exposes the same private names and dates as the read-only review, so every opening is audited like a review.
 */
final class GetEventStudioAction
{
    public function __construct(
        private readonly EventEditorPayload $payload,
        private readonly EventEntitlements $entitlements,
        private readonly AuditLogger $auditLogger,
    ) {}

    /** @return array<string, mixed> */
    public function execute(User $admin, Event $event): array
    {
        Gate::forUser($admin)->authorize('administerDesign', $event);
        $event->loadMissing(['template.assets', 'design', 'user']);
        $payload = $this->payload->build($event);

        $this->auditLogger->record(
            actor: $admin,
            action: AuditAction::EventDraftViewed,
            subjectType: AuditSubjectType::Event,
            subjectId: (int) $event->getKey(),
            metadata: ['ownerId' => $event->user_id, 'revision' => $payload['revision'], 'source' => 'studio'],
        );

        return [
            ...$payload,
            'mode' => 'event-studio',
            'owner' => ['name' => $event->user->name, 'email' => $event->user->email],
            'lockedFeatures' => array_map(
                static fn (TemplateFeature $feature): string => $feature->value,
                $this->entitlements->locked($event),
            ),
        ];
    }
}
