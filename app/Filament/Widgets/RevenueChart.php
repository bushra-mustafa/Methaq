<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Enums\PaymentGateway;
use App\Domains\Payments\Models\Order;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

/** Approved revenue per day, in dinars, over the last week, month or quarter. */
final class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'الإيرادات اليومية (د.ل)';

    protected ?string $maxHeight = '260px';

    public ?string $filter = '30';

    /** @return array<string, string> */
    protected function getFilters(): ?array
    {
        return ['7' => 'آخر 7 أيام', '30' => 'آخر 30 يوماً', '90' => 'آخر 90 يوماً'];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $days = max(1, (int) $this->filter);
        $start = CarbonImmutable::now()->subDays($days - 1)->startOfDay();

        $byDay = Order::query()
            ->where('gateway', PaymentGateway::ManualTransfer->value)
            ->where('status', OrderStatus::Completed->value)
            ->where('completed_at', '>=', $start)
            ->get(['amount', 'completed_at'])
            ->groupBy(static fn (Order $order): string => $order->completed_at->format('Y-m-d'))
            ->map(static fn ($orders): float => round(((int) $orders->sum('amount')) / 1000, 3));

        $dates = collect(range(0, $days - 1))->map(static fn (int $offset) => $start->addDays($offset));

        return [
            'datasets' => [[
                'label' => 'الإيراد',
                'data' => $dates->map(static fn (CarbonImmutable $date): float => (float) ($byDay[$date->format('Y-m-d')] ?? 0))->all(),
                'fill' => true,
                'tension' => 0.35,
                'borderColor' => '#3b5a3f',
                'backgroundColor' => 'rgba(59, 90, 63, 0.12)',
            ]],
            'labels' => $dates->map(static fn (CarbonImmutable $date): string => $date->format('d/m'))->all(),
        ];
    }
}
