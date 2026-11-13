<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Editor\Enums\TemplateFeature;
use App\Domains\Editor\Services\DesignDocumentHydrator;
use App\Domains\Editor\Services\PublishedFeatureSanitizer;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Events\Services\EventPublishEligibility;
use App\Domains\Events\Services\InvitationAddress;
use App\Domains\Payments\Services\EventEntitlements;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/** What the owner's publish panel shows: whether publishing is possible and what it will drop. */
final class GetEventPublishStateAction
{
    public function __construct(
        private readonly EventPublishEligibility $eligibility,
        private readonly EventEntitlements $entitlements,
        private readonly DesignDocumentHydrator $hydrator,
        private readonly PublishedFeatureSanitizer $sanitizer,
    ) {}

    /** @return array{state: 'published'|'ready'|'blocked', canPublish: bool, blocker: string|null, willRemove: list<string>, invitationUrl: string} */
    public function execute(User $owner, Event $event): array
    {
        Gate::forUser($owner)->authorize('publish', $event);
        $event->loadMissing(['template', 'design']);

        $order = $this->entitlements->completedOrder($event);
        $blocker = $this->eligibility->blocker($event, $order);
        $willRemove = [];

        if ($blocker === null) {
            try {
                $design = $event->design;
                $document = $this->hydrator->hydrate([
                    'schemaVersion' => $design?->schema_version,
                    'canvas' => $design?->design_json,
                    'palette' => $design?->palette_json,
                    'scene' => $design?->scene_json,
                ]);
                $willRemove = array_map(
                    static fn (TemplateFeature $feature): string => $feature->getLabel(),
                    $this->sanitizer->sanitize($document->scene, $this->entitlements->locked($event, $order))['removed'],
                );
            } catch (InvalidArgumentException) {
                $blocker = 'التصميم يحتاج مراجعة قبل النشر.';
            }
        }

        return [
            'state' => $event->status === EventStatus::Published ? 'published' : ($blocker === null ? 'ready' : 'blocked'),
            'canPublish' => $blocker === null,
            'blocker' => $event->status === EventStatus::Published ? null : $blocker,
            'willRemove' => $willRemove,
            'invitationUrl' => InvitationAddress::url($event->subdomain),
        ];
    }
}
