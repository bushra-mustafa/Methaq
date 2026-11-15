<?php

declare(strict_types=1);

namespace App\Filament\Resources\WhatsAppMessages;

use App\Domains\Users\Actions\RefreshWhatsAppMessageAction;
use App\Domains\Users\Actions\ResendWhatsAppMessageAction;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use App\Domains\Users\Models\WhatsAppMessage;
use App\Domains\Users\Services\WhatsAppFailures;
use App\Filament\Resources\WhatsAppMessages\Pages\ManageWhatsAppMessages;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class WhatsAppMessageResource extends Resource
{
    protected static ?string $model = WhatsAppMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'رسائل واتساب';

    protected static ?string $pluralModelLabel = 'رسائل واتساب';

    protected static string|\UnitEnum|null $navigationGroup = 'النظام';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isActive() && $user->role === UserRole::Admin && $user->hasEnabledTwoFactorAuthentication();
    }

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

    public static function getNavigationBadge(): ?string
    {
        $count = WhatsAppFailures::count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withExists('resends');
    }

    public static function table(Table $table): Table
    {
        $statuses = ['pending' => 'بانتظار الإرسال', 'queued' => 'في طابور الخدمة', 'sent' => 'أُرسلت', 'acknowledged' => 'ورد تأكيد التسليم أو القراءة', 'failed' => 'فشلت', 'cancelled' => 'أُلغيت'];

        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('created_at')->label('الوقت')->dateTime('d/m/Y H:i')->timezone('Africa/Tripoli'),
            TextColumn::make('recipient')->label('الرقم')->formatStateUsing(fn (string $state): string => '••••'.substr($state, -4)),
            TextColumn::make('deduplication_key')->label('المرجع')->limit(48),
            TextColumn::make('status')->label('الحالة')->badge()->formatStateUsing(fn (string $state): string => $statuses[$state] ?? $state),
            TextColumn::make('attempts')->label('محاولات الإرسال'),
            TextColumn::make('failure_code')->label('سبب الفشل')->formatStateUsing(fn (?string $state): string => WhatsAppFailures::label($state))->placeholder('—')->wrap(),
            TextColumn::make('next_attempt_at')->state(fn (WhatsAppMessage $record) => $record->status === 'pending' ? $record->next_attempt_at : null)->label('المحاولة التالية')->dateTime('d/m/Y H:i')->timezone('Africa/Tripoli'),
        ])->recordActions([
            Action::make('refreshStatus')->label('تحديث الحالة')->icon(Heroicon::OutlinedArrowPath)
                ->visible(fn (WhatsAppMessage $record): bool => $record->provider_message_id !== null && in_array($record->status, ['queued', 'sent'], true))
                ->action(function (WhatsAppMessage $record): void {
                    $actor = auth()->user();
                    abort_unless($actor instanceof User, 403);
                    app(RefreshWhatsAppMessageAction::class)->execute($actor, (int) $record->getKey());
                    Notification::make()->success()->title('تحدّثت حالة الرسالة من الخدمة')->send();
                }),
            Action::make('retry')->label('إعادة الإرسال')->icon(Heroicon::OutlinedArrowPath)
                ->visible(fn (WhatsAppMessage $record): bool => $record->status === 'failed' && $record->kind !== 'verification' && ! str_starts_with($record->deduplication_key, 'verify-') && $record->expires_at->isFuture() && ! $record->resends_exists)
                ->requiresConfirmation()->modalHeading('تأكيد إعادة إرسال الرسالة')
                ->modalDescription('راجعي الرقم قبل الإرسال. إشعارات الإدارة تستخدم رقم الاستقبال المحفوظ حالياً؛ تعديل رقمها يكون من إعدادات واتساب.')
                ->fillForm(fn (WhatsAppMessage $record): array => ['recipient' => app(ResendWhatsAppMessageAction::class)->recipient($record)])
                ->schema([TextInput::make('recipient')->label('رقم المستلم')->readOnly()->required()->extraInputAttributes(['dir' => 'ltr'])])
                ->action(function (WhatsAppMessage $record, array $data): void {
                    $actor = auth()->user();
                    abort_unless($actor instanceof User, 403);
                    app(ResendWhatsAppMessageAction::class)->execute($actor, (int) $record->getKey(), (string) $data['recipient']);
                    Notification::make()->success()->title('أُضيفت الرسالة لطابور الإرسال')->body('تظهر نتيجة الإرسال بعد المعالجة التلقائية.')->send();
                }),
        ])->poll('15s')->filters([SelectFilter::make('status')->label('الحالة')->options($statuses)]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageWhatsAppMessages::route('/')];
    }
}
