<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders;

use App\Domains\Payments\Actions\ApprovePaymentAction;
use App\Domains\Payments\Actions\RejectPaymentAction;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Enums\PaymentGateway;
use App\Domains\Payments\Enums\PaymentRejectionReason;
use App\Domains\Payments\Exceptions\PaymentReviewException;
use App\Domains\Payments\Models\Order;
use App\Domains\Payments\Services\LydAmount;
use App\Domains\Users\Models\User;
use App\Filament\Resources\Orders\Pages\ManageOrders;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Bank-transfer receipts awaiting an administrator's decision: approve or reject only.
 * Orders are never created, edited or deleted here, and every decision goes through a
 * domain Action (policy + lock + audit). Approval confirms the money arrived; it does not publish.
 */
final class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $modelLabel = 'قسيمة';

    protected static ?string $pluralModelLabel = 'قسائم التحويل';

    protected static ?string $navigationLabel = 'قسائم التحويل';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /** @return Builder<Order> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('gateway', PaymentGateway::ManualTransfer->value)
            ->with('user');
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Order::query()
            ->where('gateway', PaymentGateway::ManualTransfer->value)
            ->where('status', OrderStatus::Pending->value)
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(self::statusLabels())
                    ->default(OrderStatus::Pending->value),
            ])
            ->columns([
                ImageColumn::make('receipt')
                    ->label('القسيمة')
                    ->state(fn (Order $record): ?string => self::isPdf($record) ? null : route('filament.admin.receipts.show', $record))
                    ->imageHeight(64),
                TextColumn::make('user.name')->label('المستخدم')->searchable(),
                TextColumn::make('amount')
                    ->label('قيمة الطلب')
                    ->formatStateUsing(fn (string $state): string => LydAmount::format((int) $state)),
                TextColumn::make('created_at')
                    ->label('تاريخ الرفع')
                    ->dateTime('d/m/Y H:i')
                    ->timezone((string) config('methaq.invitation_timezone', 'Africa/Tripoli')),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => self::statusLabels()[$state->value])
                    ->color(fn (OrderStatus $state): string => match ($state) {
                        OrderStatus::Pending => 'warning',
                        OrderStatus::Completed => 'success',
                        OrderStatus::Failed => 'danger',
                    }),
                TextColumn::make('reviewed_at')
                    ->label('تاريخ القرار')
                    ->dateTime('d/m/Y H:i')
                    ->timezone((string) config('methaq.invitation_timezone', 'Africa/Tripoli'))
                    ->placeholder('—'),
            ])
            ->recordActions([
                self::receiptAction(),
                self::approveAction(),
                self::rejectAction(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOrders::route('/'),
        ];
    }

    /** @return array<string, string> */
    private static function statusLabels(): array
    {
        return [
            OrderStatus::Pending->value => 'بانتظار المراجعة',
            OrderStatus::Completed->value => 'موافق عليها',
            OrderStatus::Failed->value => 'مرفوضة',
        ];
    }

    private static function isPdf(Order $order): bool
    {
        return str_ends_with(strtolower((string) $order->receipt_path), '.pdf');
    }

    private static function receiptAction(): Action
    {
        return Action::make('receipt')
            ->label('عرض القسيمة')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->url(fn (Order $record): string => route('filament.admin.receipts.show', $record))
            ->openUrlInNewTab();
    }

    private static function approveAction(): Action
    {
        return Action::make('approve')
            ->label('موافقة')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->authorize('approve')
            ->visible(fn (Order $record): bool => $record->status === OrderStatus::Pending)
            ->requiresConfirmation()
            ->modalHeading(fn (Order $record): string => 'اعتماد الدفع · '.LydAmount::format((int) $record->amount))
            ->modalDescription(fn (Order $record): string => 'تأكدي من وصول المبلغ لحسابك قبل الموافقة.'
                .(self::hasDuplicateReceipt($record) ? ' تنبيه: ظهرت قسيمة بالمحتوى نفسه في طلب آخر.' : ''))
            ->modalSubmitActionLabel('تأكيد الموافقة')
            ->action(function (Order $record): void {
                try {
                    app(ApprovePaymentAction::class)->execute(self::actor(), (int) $record->getKey());
                } catch (PaymentReviewException $exception) {
                    Notification::make()->danger()->title($exception->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title('اعتُمد الدفع')->send();
            });
    }

    private static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('رفض')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->authorize('reject')
            ->visible(fn (Order $record): bool => $record->status === OrderStatus::Pending)
            ->modalHeading('رفض القسيمة')
            ->modalDescription('يظهر السبب لصاحبة المناسبة لترفع قسيمة جديدة.')
            ->schema([
                Select::make('reason')
                    ->label('سبب الرفض')
                    ->options(PaymentRejectionReason::class)
                    ->required(),
            ])
            ->action(function (Order $record, array $data): void {
                $reason = $data['reason'] instanceof PaymentRejectionReason ? $data['reason'] : PaymentRejectionReason::from((string) $data['reason']);

                try {
                    app(RejectPaymentAction::class)->execute(self::actor(), (int) $record->getKey(), $reason);
                } catch (PaymentReviewException $exception) {
                    Notification::make()->danger()->title($exception->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title('رُفضت القسيمة')->send();
            });
    }

    private static function hasDuplicateReceipt(Order $order): bool
    {
        return $order->receipt_sha256 !== null
            && Order::query()->where('receipt_sha256', $order->receipt_sha256)->whereKeyNot($order->getKey())->exists();
    }

    private static function actor(): User
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
