<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Editor\Enums\TemplateFeature;
use App\Domains\Events\Models\Event;
use App\Domains\Events\Services\EventPublishEligibility;
use App\Domains\Events\Services\InvitationAddress;
use App\Domains\Events\Services\PublishedSnapshotWriter;
use App\Domains\Payments\Services\EventEntitlements;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** What the studio's refresh panel shows: whether the live invitation can take the saved draft, and what it would drop. */
final class GetPublishedInvitationRefreshStateAction
{
    public function __construct(
        private readonly EventPublishEligibility $eligibility,
        private readonly EventEntitlements $entitlements,
        private readonly PublishedSnapshotWriter $snapshots,
    ) {}

    /** @return array{canRefresh: bool, blocker: string|null, willRemove: list<string>, publishedRevision: int|null, invitationUrl: string} */
    public function execute(User $admin, Event $event): array
    {
        Gate::forUser($admin)->authorize('administerDesign', $event);
        $event->loadMissing(['template', 'design']);

        $order = $this->entitlements->completedOrder($event);
        $blocker = $this->eligibility->refreshBlocker($event, $order);
        $willRemove = [];

        if ($blocker === null && $event->design !== null) {
            try {
                $willRemove = array_map(
                    static fn (TemplateFeature $feature): string => $feature->getLabel(),
                    $this->snapshots->prepare($event, $event->design, $order)->removed,
                );
            } catch (ValidationException) {
                $blocker = 'التصميم يحتاج مراجعة قبل النشر.';
            }
        }

        return [
            'canRefresh' => $blocker === null,
            'blocker' => $blocker,
            'willRemove' => $willRemove,
            'publishedRevision' => $event->design?->published_revision,
            'invitationUrl' => InvitationAddress::url($event->subdomain),
        ];
    }
}
