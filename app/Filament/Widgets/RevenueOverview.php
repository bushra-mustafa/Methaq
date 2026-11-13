<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Enums\PaymentGateway;
use App\Domains\Payments\Models\Order;
use App\Domains\Payments\Services\LydAmount;
use Carbon\CarbonImmutable;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/** The money at a glance: what has been approved, this month against last, and what still waits. */
final class RevenueOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'المالية';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $now = CarbonImmutable::now();
        $completed = $this->orders(OrderStatus::Completed);

        $total = (int) (clone $completed)->sum('amount');
        $count = (clone $completed)->count();
        $thisMonth = (int) (clone $completed)->where('completed_at', '>=', $now->startOfMonth())->sum('amount');
        $lastMonth = (int) (clone $completed)
            ->whereBetween('completed_at', [$now->subMonthNoOverflow()->startOfMonth(), $now->startOfMonth()])
            ->sum('amount');

        $pending = $this->orders(OrderStatus::Pending);
        $pendingTotal = (int) (clone $pending)->sum('amount');
        $pendingCount = (clone $pending)->count();

        return [
            Stat::make('إجمالي الإيرادات', LydAmount::format($total))
                ->description($count > 0 ? "من {$count} دفعة معتمدة" : 'لا دفعات معتمدة بعد')
                ->descriptionIcon(Heroicon::OutlinedCheckBadge)
                ->chart($this->lastDays(14))
                ->color('success'),
            Stat::make('هذا الشهر', LydAmount::format($thisMonth))
                ->description($this->trend($thisMonth, $lastMonth))
                ->descriptionIcon($thisMonth >= $lastMonth ? Heroicon::OutlinedArrowTrendingUp : Heroicon::OutlinedArrowTrendingDown)
                ->color($thisMonth >= $lastMonth ? 'success' : 'danger'),
            Stat::make('بانتظار الاعتماد', LydAmount::format($pendingTotal))
                ->description($pendingCount > 0 ? "{$pendingCount} قسيمة تنتظر قرارك" : 'لا شيء ينتظرك')
                ->descriptionIcon($pendingCount > 0 ? Heroicon::OutlinedClock : Heroicon::OutlinedCheckCircle)
                ->color($pendingCount > 0 ? 'warning' : 'gray'),
            Stat::make('متوسط الدفعة', LydAmount::format($count > 0 ? intdiv($total, $count) : 0))
                ->description('لكل دعوة مدفوعة')
                ->descriptionIcon(Heroicon::OutlinedCalculator)
                ->color('primary'),
        ];
    }

    /** @return Builder<Order> */
    private function orders(OrderStatus $status): Builder
    {
        return Order::query()->where('gateway', PaymentGateway::ManualTransfer->value)->where('status', $status->value);
    }

    private function trend(int $thisMonth, int $lastMonth): string
    {
        if ($lastMonth === 0) {
            return $thisMonth > 0 ? 'أول إيراد يُسجَّل' : 'مقابل الشهر الماضي: لا بيانات';
        }

        $change = (int) round((($thisMonth - $lastMonth) / $lastMonth) * 100);

        return ($change >= 0 ? '+' : '').$change.'% عن الشهر الماضي';
    }

    /** @return list<int> Approved amount per day, oldest first. */
    private function lastDays(int $days): array
    {
        $start = CarbonImmutable::now()->subDays($days - 1)->startOfDay();
        $byDay = $this->orders(OrderStatus::Completed)
            ->where('completed_at', '>=', $start)
            ->get(['amount', 'completed_at'])
            ->groupBy(static fn (Order $order): string => $order->completed_at->format('Y-m-d'))
            ->map(static fn ($orders): int => (int) $orders->sum('amount'));

        return collect(range(0, $days - 1))
            ->map(static fn (int $offset): int => (int) ($byDay[$start->addDays($offset)->format('Y-m-d')] ?? 0))
            ->all();
    }
}
