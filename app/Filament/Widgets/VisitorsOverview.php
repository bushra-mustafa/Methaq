<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domains\Events\Services\VisitStats;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** How many people open the public site. Counters only: no address, cookie or profile is kept. */
final class VisitorsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'زوار الموقع';

    protected ?string $description = 'يُحسب الزائر مرة في اليوم. لا نخزّن عنوان IP ولا كوكيز، والمحركات والمعاينات لا تُحسب.';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $site = app(VisitStats::class)->site();

        return [
            Stat::make('اليوم', number_format($site['today']['visitors']))
                ->description(number_format($site['today']['views']).' فتحة صفحة')
                ->descriptionIcon(Heroicon::OutlinedEye)
                ->color('primary'),
            Stat::make('آخر 7 أيام', number_format($site['last7']['visitors']))
                ->description(number_format($site['last7']['views']).' فتحة صفحة')
                ->descriptionIcon(Heroicon::OutlinedEye)
                ->color('success'),
            Stat::make('آخر 30 يوماً', number_format($site['last30']['visitors']))
                ->description(number_format($site['last30']['views']).' فتحة صفحة')
                ->descriptionIcon(Heroicon::OutlinedEye)
                ->color('success'),
            Stat::make('منذ البداية', number_format($site['total']['visitors']))
                ->description(number_format($site['total']['views']).' فتحة صفحة')
                ->descriptionIcon(Heroicon::OutlinedGlobeAlt)
                ->color('gray'),
        ];
    }
}
