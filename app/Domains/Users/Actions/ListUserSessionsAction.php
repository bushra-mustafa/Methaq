<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\DTOs\UserSessionData;
use App\Domains\Users\Models\User;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class ListUserSessionsAction
{
    /** @return list<UserSessionData> */
    public function execute(User $user, string $currentSessionId): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        $fingerprintKey = (string) config('app.key');

        return DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(static fn (object $session): UserSessionData => new UserSessionData(
                fingerprint: hash_hmac('sha256', (string) $session->id, $fingerprintKey),
                isCurrentDevice: hash_equals((string) $session->id, $currentSessionId),
                ipAddress: is_string($session->ip_address) ? $session->ip_address : null,
                userAgent: is_string($session->user_agent) && $session->user_agent !== '' ? $session->user_agent : 'جهاز غير معروف',
                lastActiveAt: (new DateTimeImmutable)->setTimestamp((int) $session->last_activity),
            ))
            ->all();
    }
}
