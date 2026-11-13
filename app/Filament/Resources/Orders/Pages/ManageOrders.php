<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Models\Order;
use App\Domains\Payments\Services\LydAmount;
use App\Filament\Resources\Orders\OrderResource;
use App\Http\Support\CsvCell;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ManageOrders extends ManageRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('تصدير إلى Excel')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->visible(fn (): bool => Gate::allows('viewAny', Order::class))
                ->modalHeading('تصدير القسائم')
                ->modalDescription('يُصدَّر ما يظهر في الجدول الآن بحسب الفلتر المختار. غيّري فلتر الحالة أولاً لتصدير المعتمد أو المرفوض.')
                ->modalSubmitActionLabel('تنزيل الملف')
                ->action(fn (): StreamedResponse => $this->export()),
        ];
    }

    private function export(): StreamedResponse
    {
        Gate::authorize('viewAny', Order::class);
        $query = $this->getFilteredSortedTableQuery()->with(['user', 'event.template']);
        $timezone = (string) config('methaq.invitation_timezone', 'Africa/Tripoli');
        $labels = [
            OrderStatus::Pending->value => 'بانتظار المراجعة',
            OrderStatus::Completed->value => 'موافق عليها',
            OrderStatus::Failed->value => 'مرفوضة',
        ];

        return response()->streamDownload(function () use ($query, $timezone, $labels): void {
            $out = fopen('php://output', 'w');
            // The byte-order mark makes Excel read the Arabic as UTF-8.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['رقم الطلب', 'الاسم', 'البريد', 'المناسبة', 'القالب', 'المبلغ (د.ل)', 'الحالة', 'تاريخ الرفع', 'تاريخ القرار', 'سبب الرفض']);

            $query->lazyById(200)->each(function (Order $order) use ($out, $timezone, $labels): void {
                fputcsv($out, array_map(CsvCell::safe(...), [
                    (string) $order->getKey(),
                    (string) $order->user?->name,
                    (string) $order->user?->email,
                    (string) $order->event?->title,
                    (string) $order->event?->template?->name,
                    number_format($order->amount / LydAmount::MINOR_UNITS_PER_DINAR, 3, '.', ''),
                    $labels[$order->status->value] ?? $order->status->value,
                    $order->created_at?->timezone($timezone)->format('Y-m-d H:i') ?? '',
                    $order->reviewed_at?->timezone($timezone)->format('Y-m-d H:i') ?? '',
                    (string) $order->failure_code,
                ]));
            });

            fclose($out);
        }, 'methaq-receipts-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
