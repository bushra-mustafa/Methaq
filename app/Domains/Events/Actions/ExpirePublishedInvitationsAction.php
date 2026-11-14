<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Events\Services\PublishedRenderVisibility;

/**
 * Marks published invitations whose link has closed as expired, so dashboards agree
 * with the guest link, and takes their card image off the public disk. Access never
 * depends on this for pages: ResolvePublishedEvent checks `expires_at` on every request.
 * The image is a static file, so it stays reachable until this runs (daily). Only the
 * event status and the image location change; designs, RSVPs and templates are untouched.
 */
final class ExpirePublishedInvitationsAction
{
    public function __construct(private readonly PublishedRenderVisibility $render) {}

    public function execute(): int
    {
        $count = 0;

        Event::query()
            ->with('design')
            ->where('status', EventStatus::Published->value)
            ->where('expires_at', '<=', now())
            ->lazyById(100)
            ->each(function (Event $event) use (&$count): void {
                $event->forceFill(['status' => EventStatus::Expired, 'updated_at' => now()])->save();
                $this->render->hold($event);
                $count++;
            });

        return $count;
    }
}
