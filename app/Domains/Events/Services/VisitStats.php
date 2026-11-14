<?php

declare(strict_types=1);

namespace App\Domains\Events\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Reads the daily counters VisitCounter keeps. "Visitors" adds up each day's different visitors, so one person on two days counts twice. */
final class VisitStats
{
    /** @return array{views: int, visitors: int, last7Views: int, last7Visitors: int} */
    public function forEvent(int $eventId): array
    {
        $rows = DB::table('event_visit_days')->where('event_id', $eventId);

        return [
            ...$this->totals(clone $rows),
            ...$this->recent(clone $rows, 7, 'last7'),
        ];
    }

    /** @return array{today: array{views: int, visitors: int}, last7: array{views: int, visitors: int}, last30: array{views: int, visitors: int}, total: array{views: int, visitors: int}} */
    public function site(): array
    {
        return [
            'today' => $this->range('site_visit_days', 1),
            'last7' => $this->range('site_visit_days', 7),
            'last30' => $this->range('site_visit_days', 30),
            'total' => $this->range('site_visit_days', null),
        ];
    }

    /** @return array{views: int, visitors: int} the last `$days` days including today, or everything when null */
    public function range(string $table, ?int $days): array
    {
        $query = DB::table($table);
        if ($days !== null) {
            $query->where('day', '>=', CarbonImmutable::parse(VisitCounter::today())->subDays($days - 1)->toDateString());
        }
        $row = $query->selectRaw('COALESCE(SUM(views), 0) AS views, COALESCE(SUM(visitors), 0) AS visitors')->first();

        return ['views' => (int) ($row->views ?? 0), 'visitors' => (int) ($row->visitors ?? 0)];
    }

    /**
     * @param  Builder  $query
     * @return array{views: int, visitors: int}
     */
    private function totals($query): array
    {
        $row = $query->selectRaw('COALESCE(SUM(views), 0) AS views, COALESCE(SUM(visitors), 0) AS visitors')->first();

        return ['views' => (int) ($row->views ?? 0), 'visitors' => (int) ($row->visitors ?? 0)];
    }

    /**
     * @param  Builder  $query
     * @return array<string, int>
     */
    private function recent($query, int $days, string $prefix): array
    {
        $row = $query->where('day', '>=', CarbonImmutable::parse(VisitCounter::today())->subDays($days - 1)->toDateString())
            ->selectRaw('COALESCE(SUM(views), 0) AS views, COALESCE(SUM(visitors), 0) AS visitors')->first();

        return [$prefix.'Views' => (int) ($row->views ?? 0), $prefix.'Visitors' => (int) ($row->visitors ?? 0)];
    }
}
