<?php

declare(strict_types=1);

namespace App\Filament\Resources\Events;

use App\Domains\Events\Actions\ReinstateEventAction;
use App\Domains\Events\Actions\SuspendEventAction;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Events\Services\InvitationAddress;
use App\Domains\Users\Models\User;
use App\Filament\Resources\Events\Pages\ManageEvents;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Every customer invitation, with who owns it, whether it is paid and live. Administrators can
 * open a published invitation or take it offline with a reason; content is never edited here.
 */
final class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static ?string $modelLabel = 'مناسبة';

    protected static ?string $pluralModelLabel = 'المناسبات';

    protected static ?string $navigationLabel = 'المناسبات';

    protected static string|\UnitEnum|null $navigationGroup = 'التشغيل';

    protected static ?int $navigationSort = 3;

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

    /** @return Builder<Event> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'template'])->withCount('rsvps')->withSum('visitDays as views_total', 'views')->withSum('visitDays as visitors_total', 'visitors');
    }

    public static function table(Table $table): Table
    {
        $timezone = (string) config('methaq.invitation_timezone', 'Africa/Tripoli');

        return $table
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50])
            ->columns([
                TextColumn::make('title')
                    ->label('المناسبة')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Event $record): string => (string) $record->template?->name ?: 'بطاقة فارغة'),
                TextColumn::make('user.name')->label('صاحبة المناسبة')->searchable()->icon(Heroicon::OutlinedUser),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (EventStatus $state): string => self::statusLabels()[$state->value])
                    ->color(fn (EventStatus $state): string => match ($state) {
                        EventStatus::Draft => 'gray',
                        EventStatus::Published => 'success',
                        EventStatus::Expired => 'warning',
                    }),
                IconColumn::make('is_paid')->label('مدفوعة')->boolean(),
                IconColumn::make('suspended_at')
                    ->label('الإيقاف')
                    ->state(fn (Event $record): bool => $record->suspended_at !== null)
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedNoSymbol)
                    ->falseIcon(Heroicon::OutlinedCheckCircle)
                    ->trueColor('danger')
                    ->falseColor('gray'),
                TextColumn::make('event_date')->label('موعد المناسبة')->dateTime('d/m/Y H:i')->timezone($timezone)->sortable(),
                TextColumn::make('visitors_total')->label('الزوار')->numeric()->state(fn (Event $record): int => (int) $record->visitors_total)->icon(Heroicon::OutlinedUsers)->sortable(),
                TextColumn::make('views_total')->label('الفتحات')->numeric()->state(fn (Event $record): int => (int) $record->views_total)->icon(Heroicon::OutlinedEye)->sortable(),
                TextColumn::make('rsvps_count')->label('ردود الضيوف')->numeric()->icon(Heroicon::OutlinedChatBubbleLeftRight),
                TextColumn::make('published_at')->label('نُشرت')->dateTime('d/m/Y')->timezone($timezone)->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options(self::statusLabels()),
                Filter::make('paid')->label('مدفوعة فقط')->query(fn (Builder $query): Builder => $query->where('is_paid', true)),
                Filter::make('suspended')->label('الموقوفة فقط')->query(fn (Builder $query): Builder => $query->whereNotNull('suspended_at')),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('فتح الدعوة')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->visible(fn (Event $record): bool => $record->status === EventStatus::Published)
                    ->url(fn (Event $record): string => InvitationAddress::url($record->subdomain))
                    ->openUrlInNewTab(),
                Action::make('draft')
                    ->label('عرض المسودة')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->authorize('reviewDraft')
                    ->url(fn (Event $record): string => route('app.review.events.show', $record))
                    ->openUrlInNewTab(),
                self::suspendAction(),
                self::reinstateAction(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageEvents::route('/')];
    }

    /** @return array<string, string> */
    private static function statusLabels(): array
    {
        return [
            EventStatus::Draft->value => 'مسودة',
            EventStatus::Published->value => 'منشورة',
            EventStatus::Expired->value => 'منتهية',
        ];
    }

    private static function suspendAction(): Action
    {
        return Action::make('suspend')
            ->label('إيقاف')
            ->icon(Heroicon::OutlinedPause)
            ->color('danger')
            ->authorize('moderate')
            ->visible(fn (Event $record): bool => $record->suspended_at === null)
            ->modalHeading('إيقاف الدعوة')
            ->modalDescription('يتوقف رابط الدعوة عن الفتح فوراً. اكتبي السبب ليُسجَّل في سجل التدقيق.')
            ->schema([Textarea::make('reason')->label('السبب')->required()->maxLength(500)])
            ->action(function (Event $record, array $data): void {
                app(SuspendEventAction::class)->execute(self::actor(), (int) $record->getKey(), (string) $data['reason']);
                Notification::make()->success()->title('أُوقفت الدعوة')->send();
            });
    }

    private static function reinstateAction(): Action
    {
        return Action::make('reinstate')
            ->label('إعادة التفعيل')
            ->icon(Heroicon::OutlinedPlay)
            ->color('success')
            ->authorize('moderate')
            ->visible(fn (Event $record): bool => $record->suspended_at !== null)
            ->requiresConfirmation()
            ->modalHeading('إعادة تفعيل الدعوة؟')
            ->action(function (Event $record): void {
                app(ReinstateEventAction::class)->execute(self::actor(), (int) $record->getKey());
                Notification::make()->success()->title('عادت الدعوة للعمل')->send();
            });
    }

    private static function actor(): User
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
