<?php

declare(strict_types=1);

namespace App\Domains\Payments\Actions;

use App\Domains\Editor\Actions\GetEventEditorAction;
use App\Domains\Editor\Services\DesignDocumentHydrator;
use App\Domains\Editor\Services\PublishedFeatureSanitizer;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Services\EventEntitlements;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Gate;

final class GetPaymentPreviewAction
{
    public function __construct(private readonly GetEventEditorAction $editor, private readonly DesignDocumentHydrator $hydrator, private readonly PublishedFeatureSanitizer $sanitizer, private readonly EventEntitlements $entitlements) {}

    public function execute(User $owner, Event $event): ?array
    {
        Gate::forUser($owner)->authorize('view', $event);
        $event->loadMissing('design');
        if ($event->status !== EventStatus::Draft || $event->design === null) {
            return null;
        }
        $preview = $this->editor->execute($owner, $event);
        $document = $this->hydrator->hydrate($preview['document']);
        $preview['document']['scene'] = $this->sanitizer->sanitize($document->scene, $this->entitlements->locked($event))['scene']->toArray();

        return $preview;
    }
}
