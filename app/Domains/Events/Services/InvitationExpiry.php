<?php

declare(strict_types=1);

namespace App\Domains\Events\Services;

use Carbon\CarbonImmutable;

/**
 * When a guest link closes: midnight after the event day plus the grace days, in the
 * invitation timezone (Africa/Tripoli by default). Example: an event on 14/8 with a
 * two-day grace stays open through 16/8 and closes at 17/8 00:00 Tripoli time.
 */
final class InvitationExpiry
{
    public const MAX_GRACE_DAYS = 7;

    public function expiresAt(CarbonImmutable $eventDate): CarbonImmutable
    {
        return $eventDate
            ->setTimezone($this->timezone())
            ->startOfDay()
            ->addDays($this->graceDays() + 1)
            ->setTimezone('UTC');
    }

    public function graceDays(): int
    {
        return max(0, min(self::MAX_GRACE_DAYS, (int) config('methaq.invitation_grace_days', 2)));
    }

    public function timezone(): string
    {
        return (string) config('methaq.invitation_timezone', 'Africa/Tripoli');
    }
}
