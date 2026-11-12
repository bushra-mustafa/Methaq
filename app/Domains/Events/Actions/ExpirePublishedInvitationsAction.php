<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;

/**
 * Marks published invitations whose link has closed as expired, so dashboards agree
 * with the guest link. Access never depends on this: ResolvePublishedEvent checks
 * `expires_at` on every request. Only the event status changes; designs, RSVPs and
 * templates are untouched.
 */
final class ExpirePublishedInvitationsAction
{
    public function execute(): int
    {
        return Event::query()
            ->where('status', EventStatus::Published->value)
            ->where('expires_at', '<=', now())
            ->update(['status' => EventStatus::Expired->value, 'updated_at' => now()]);
    }
}
