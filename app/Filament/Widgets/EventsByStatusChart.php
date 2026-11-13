<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use Filament\Widgets\ChartWidget;

/** Where every customer invitation stands right now. */
final class EventsByStatusChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'حالة المناسبات';

    protected ?string $maxHeight = '260px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $suspended = Event::query()->whereNotNull('suspended_at')->count();
        $count = static fn (EventStatus $status): int => Event::query()->where('status', $status->value)->whereNull('suspended_at')->count();

        return [
            'datasets' => [[
                'data' => [$count(EventStatus::Draft), $count(EventStatus::Published), $count(EventStatus::Expired), $suspended],
                'backgroundColor' => ['#b8b2a4', '#3b5a3f', '#c9a46b', '#b91c1c'],
                'borderWidth' => 0,
            ]],
            'labels' => ['مسودات', 'منشورة', 'منتهية', 'موقوفة'],
        ];
    }
}
