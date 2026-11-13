<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users;

use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Services\LydAmount;
use App\Domains\Users\Actions\ReinstateUserAction;
use App\Domains\Users\Actions\SuspendUserAction;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Exceptions\ModerationException;
use App\Domains\Users\Models\User;
use App\Filament\Resources\Users\Pages\ManageUsers;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Accounts at a glance: how many invitations each person made, what they have paid and whether
 * the account is in good standing. Administrators can suspend or reinstate customers, with a reason.
 */
final class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'مستخدمة';

    protected static ?string $pluralModelLabel = 'المستخدمات';

    protected static ?string $navigationLabel = 'المستخدمات';

    protected static string|\UnitEnum|null $navigationGroup = 'الناس';

    protected static ?int $navigationSort = 4;

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

    /** @return Builder<User> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('events')
            ->withSum(['orders as paid_total' => fn (Builder $query): Builder => $query->where('status', OrderStatus::Completed->value)], 'amount');
    }

    public static function table(Table $table): Table
    {
        $timezone = (string) config('methaq.invitation_timezone', 'Africa/Tripoli');

        return $table
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50])
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (User $record): string => $record->email),
                TextColumn::make('phone')->label('الهاتف')->placeholder('—')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('role')
                    ->label('الدور')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state === UserRole::Admin ? 'مديرة' : 'عميلة')
                    ->color(fn (UserRole $state): string => $state === UserRole::Admin ? 'primary' : 'gray'),
                TextColumn::make('events_count')->label('مناسباتها')->numeric()->icon(Heroicon::OutlinedEnvelopeOpen)->sortable(),
                TextColumn::make('paid_total')
                    ->label('إجمالي ما دفعته')
                    ->state(fn (User $record): string => LydAmount::format((int) $record->paid_total))
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->sortable(),
                IconColumn::make('email_verified_at')
                    ->label('البريد مؤكد')
                    ->state(fn (User $record): bool => $record->email_verified_at !== null)
                    ->boolean(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (UserStatus $state): string => $state === UserStatus::Active ? 'نشطة' : 'موقوفة')
                    ->color(fn (UserStatus $state): string => $state === UserStatus::Active ? 'success' : 'danger'),
                TextColumn::make('created_at')->label('انضمت')->dateTime('d/m/Y')->timezone($timezone)->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options([UserStatus::Active->value => 'نشطة', UserStatus::Suspended->value => 'موقوفة']),
                SelectFilter::make('role')->label('الدور')->options([UserRole::Customer->value => 'عميلة', UserRole::Admin->value => 'مديرة']),
            ])
            ->recordActions([self::suspendAction(), self::reinstateAction()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }

    private static function suspendAction(): Action
    {
        return Action::make('suspend')
            ->label('إيقاف')
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('danger')
            ->authorize('changeStatus')
            ->visible(fn (User $record): bool => $record->status === UserStatus::Active && $record->role === UserRole::Customer)
            ->modalHeading(fn (User $record): string => 'إيقاف حساب '.$record->name)
            ->modalDescription('تنتهي جلساتها ويتوقف فتح دعواتها. اكتبي السبب ليُسجَّل في سجل التدقيق.')
            ->schema([Textarea::make('reason')->label('السبب')->required()->maxLength(500)])
            ->action(function (User $record, array $data): void {
                try {
                    app(SuspendUserAction::class)->execute(self::actor(), (int) $record->getKey(), (string) $data['reason']);
                } catch (ModerationException $exception) {
                    Notification::make()->danger()->title($exception->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title('أُوقف الحساب')->send();
            });
    }

    private static function reinstateAction(): Action
    {
        return Action::make('reinstate')
            ->label('إعادة التفعيل')
            ->icon(Heroicon::OutlinedLockOpen)
            ->color('success')
            ->authorize('changeStatus')
            ->visible(fn (User $record): bool => $record->status === UserStatus::Suspended)
            ->requiresConfirmation()
            ->modalHeading(fn (User $record): string => 'إعادة تفعيل حساب '.$record->name.'؟')
            ->action(function (User $record): void {
                app(ReinstateUserAction::class)->execute(self::actor(), (int) $record->getKey());
                Notification::make()->success()->title('عاد الحساب نشطاً')->send();
            });
    }

    private static function actor(): User
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
