<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domains\Editor\Models\Template;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** The four numbers an administrator checks first: what waits for review and what is live. */
final class PlatformStats extends StatsOverviewWidget
{
    protected ?string $heading = 'نظرة على ميثاق';

    protected function getStats(): array
    {
        $pending = Order::query()->where('status', OrderStatus::Pending)->count();

        return [
            Stat::make('قسائم بانتظار المراجعة', $pending)
                ->description($pending > 0 ? 'افتحي «قسائم التحويل» للقرار' : 'لا شيء ينتظرك')
                ->color($pending > 0 ? 'warning' : 'success'),
            Stat::make('دعوات منشورة', Event::query()->where('status', EventStatus::Published)->count())
                ->color('primary'),
            Stat::make('مسودات', Event::query()->where('status', EventStatus::Draft)->count())
                ->description('مناسبات لم تُنشر بعد'),
            Stat::make('قوالب فعّالة', Template::query()->where('is_active', true)->count())
                ->description('ظاهرة في المكتبة'),
        ];
    }
}
