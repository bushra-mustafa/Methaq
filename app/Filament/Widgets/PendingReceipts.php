<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Enums\PaymentGateway;
use App\Domains\Payments\Models\Order;
use App\Domains\Payments\Services\LydAmount;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** The receipts that wait for a decision, oldest first, with a shortcut to the review screen. */
final class PendingReceipts extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('قسائم تنتظر قرارك')
            ->query(fn () => Order::query()
                ->where('gateway', PaymentGateway::ManualTransfer->value)
                ->where('status', OrderStatus::Pending->value)
                ->with(['user', 'event'])
                ->oldest())
            ->paginated(false)
            ->columns([
                TextColumn::make('user.name')->label('صاحبة المناسبة')->icon(Heroicon::OutlinedUser)->weight('bold'),
                TextColumn::make('event.title')->label('المناسبة')->limit(40),
                TextColumn::make('amount')->label('المبلغ')->formatStateUsing(fn (string $state): string => LydAmount::format((int) $state)),
                TextColumn::make('created_at')->label('منذ')->since(),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('مراجعة')
                    ->icon(Heroicon::OutlinedArrowLeftCircle)
                    ->url(OrderResource::getUrl('index')),
            ])
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle)
            ->emptyStateHeading('لا شيء ينتظرك')
            ->emptyStateDescription('كل القسائم المرفوعة تم البتّ فيها.');
    }
}
