<?php

declare(strict_types=1);

namespace App\Domains\Events\Services;

use App\Domains\Events\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Counts how many times an invitation or the public site is opened, and how many different visitors that was each day.
 *
 * Nothing that identifies a person is stored. To tell a new visitor from a returning one within the same day, a one-way
 * hash of address and browser (mixed with the day and the app key) sits in the cache for a day and is then gone; the
 * database only keeps the daily totals. Crawlers, previews and prefetches are not counted. A counting failure never
 * affects the page.
 */
final class VisitCounter
{
    private const ROBOTS = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|curl|wget|python-requests|headless|monitor|uptime|lighthouse/i';

    public function recordEventView(Event $event, Request $request): void
    {
        $this->record('event_visit_days', ['event_id' => (int) $event->getKey()], 'event:'.$event->getKey(), $request);
    }

    public function recordSiteView(Request $request): void
    {
        $this->record('site_visit_days', [], 'site', $request);
    }

    public static function today(): string
    {
        return CarbonImmutable::now((string) config('methaq.invitation_timezone', 'Africa/Tripoli'))->toDateString();
    }

    /** @param array<string, int> $keys */
    private function record(string $table, array $keys, string $scope, Request $request): void
    {
        try {
            if (! $this->countable($request)) {
                return;
            }

            $day = self::today();
            $newVisitor = Cache::add(
                'visit:'.$day.':'.$scope.':'.hash('sha256', $request->ip().'|'.$request->userAgent().'|'.$day.'|'.config('app.key')),
                1,
                now()->addHours(26),
            ) ? 1 : 0;

            $columns = [...array_keys($keys), 'day', 'views', 'visitors'];
            DB::statement(
                'INSERT INTO `'.$table.'` ('.implode(', ', array_map(static fn (string $column): string => '`'.$column.'`', $columns)).') VALUES ('.implode(', ', array_fill(0, count($keys) + 1, '?')).', 1, ?) ON DUPLICATE KEY UPDATE `views` = `views` + 1, `visitors` = `visitors` + ?',
                [...array_values($keys), $day, $newVisitor, $newVisitor],
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function countable(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        $purpose = strtolower((string) ($request->header('Purpose') ?? $request->header('Sec-Purpose') ?? ''));
        $agent = (string) $request->userAgent();

        return ! str_contains($purpose, 'prefetch') && $agent !== '' && preg_match(self::ROBOTS, $agent) !== 1;
    }
}
