<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupportThreads;

use App\Domains\Users\Actions\ChangeSupportThreadStatusAction;
use App\Domains\Users\Actions\MarkSupportThreadReadAction;
use App\Domains\Users\Actions\ReplyToSupportThreadAction;
use App\Domains\Users\Enums\SupportThreadStatus;
use App\Domains\Users\Enums\SupportTopic;
use App\Domains\Users\Exceptions\SupportException;
use App\Domains\Users\Models\SupportThread;
use App\Domains\Users\Models\User;
use App\Filament\Resources\SupportThreads\Pages\ManageSupportThreads;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Messages from customers: problem reports and custom-design requests. An administrator reads the
 * conversation, replies, and closes it; every change goes through a domain Action (policy + lock).
 */
final class SupportThreadResource extends Resource
{
    protected static ?string $model = SupportThread::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $modelLabel = 'محادثة';

    protected static ?string $pluralModelLabel = 'الرسائل';

    protected static ?string $navigationLabel = 'الرسائل';

    protected static string|\UnitEnum|null $navigationGroup = 'الناس';

    protected static ?int $navigationSort = 5;

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

    /** @return Builder<SupportThread> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function getNavigationBadge(): ?string
    {
        $unread = SupportThread::query()->where('admin_unread', true)->where('status', SupportThreadStatus::Open->value)->count();

        return $unread > 0 ? (string) $unread : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        $timezone = (string) config('methaq.invitation_timezone', 'Africa/Tripoli');

        return $table
            ->defaultSort('last_message_at', 'desc')
            ->paginated([25, 50])
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options(SupportThreadStatus::class)->default(SupportThreadStatus::Open->value),
                SelectFilter::make('topic')->label('النوع')->options(SupportTopic::class),
            ])
            ->columns([
                IconColumn::make('admin_unread')->label('جديدة')->boolean()->trueIcon(Heroicon::OutlinedEnvelope)->falseIcon(Heroicon::OutlinedEnvelopeOpen)->trueColor('warning')->falseColor('gray'),
                TextColumn::make('user.name')->label('الاسم')->searchable()->description(fn (SupportThread $record): string => $record->user->email),
                TextColumn::make('subject')->label('العنوان')->searchable()->limit(60)->weight('bold'),
                TextColumn::make('topic')->label('النوع')->badge()->formatStateUsing(fn (SupportTopic $state): string => $state->getLabel())
                    ->color(fn (SupportTopic $state): string => $state === SupportTopic::CustomDesign ? 'primary' : 'danger'),
                TextColumn::make('status')->label('الحالة')->badge()->formatStateUsing(fn (SupportThreadStatus $state): string => $state->getLabel())
                    ->color(fn (SupportThreadStatus $state): string => $state === SupportThreadStatus::Open ? 'success' : 'gray'),
                TextColumn::make('last_message_at')->label('آخر رسالة')->dateTime('d/m/Y H:i')->timezone($timezone)->sortable(),
            ])
            ->recordActions([self::conversationAction(), self::closeAction(), self::reopenAction()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSupportThreads::route('/')];
    }

    private static function conversationAction(): Action
    {
        return Action::make('conversation')
            ->label('فتح المحادثة')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->slideOver()
            ->modalHeading(fn (SupportThread $record): string => $record->subject)
            ->modalContent(fn (SupportThread $record): View => view('filament.support.thread', ['thread' => $record->load('user', 'messages')]))
            ->modalSubmitActionLabel('إرسال الرد')
            ->modalCancelActionLabel('إغلاق النافذة')
            ->mountUsing(function (Schema $schema, SupportThread $record): void {
                app(MarkSupportThreadReadAction::class)->execute(self::actor(), $record);
                $schema->fill();
            })
            ->schema([
                Textarea::make('body')->label('ردّك')->rows(4)->maxLength(3000)->required(),
            ])
            ->modalSubmitAction(fn (Action $action, SupportThread $record) => $record->status === SupportThreadStatus::Open ? $action : false)
            ->action(function (SupportThread $record, array $data): void {
                try {
                    app(ReplyToSupportThreadAction::class)->execute(self::actor(), (int) $record->getKey(), (string) $data['body']);
                } catch (SupportException $exception) {
                    Notification::make()->danger()->title($exception->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title('أُرسل الرد')->send();
            });
    }

    private static function closeAction(): Action
    {
        return Action::make('close')
            ->label('إغلاق')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('gray')
            ->authorize('manage')
            ->visible(fn (SupportThread $record): bool => $record->status === SupportThreadStatus::Open)
            ->requiresConfirmation()
            ->modalHeading('إغلاق المحادثة')
            ->modalDescription('لن تستطيع صاحبة الحساب الرد عليها بعد الإغلاق، ويمكنك إعادة فتحها لاحقاً.')
            ->action(function (SupportThread $record): void {
                app(ChangeSupportThreadStatusAction::class)->execute(self::actor(), (int) $record->getKey(), SupportThreadStatus::Closed);
                Notification::make()->success()->title('أُغلقت المحادثة')->send();
            });
    }

    private static function reopenAction(): Action
    {
        return Action::make('reopen')
            ->label('إعادة فتح')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->authorize('manage')
            ->visible(fn (SupportThread $record): bool => $record->status === SupportThreadStatus::Closed)
            ->action(function (SupportThread $record): void {
                app(ChangeSupportThreadStatusAction::class)->execute(self::actor(), (int) $record->getKey(), SupportThreadStatus::Open);
                Notification::make()->success()->title('أُعيد فتح المحادثة')->send();
            });
    }

    private static function actor(): User
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
