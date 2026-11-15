<?php

declare(strict_types=1);

namespace App\Filament\Resources\WhatsAppMessages;

use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use App\Domains\Users\Models\WhatsAppMessage;
use App\Filament\Resources\WhatsAppMessages\Pages\ManageWhatsAppMessages;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
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

    public static function table(Table $table): Table
    {
        $statuses = ['pending' => 'بانتظار الإرسال', 'queued' => 'في طابور الخدمة', 'sent' => 'أُرسلت', 'acknowledged' => 'ورد تأكيد التسليم أو القراءة', 'failed' => 'فشلت', 'cancelled' => 'أُلغيت'];

        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('created_at')->label('الوقت')->dateTime('d/m/Y H:i')->timezone('Africa/Tripoli'),
            TextColumn::make('recipient')->label('الرقم')->formatStateUsing(fn (string $state): string => '••••'.substr($state, -4)),
            TextColumn::make('deduplication_key')->label('المرجع')->limit(48),
            TextColumn::make('status')->label('الحالة')->badge()->formatStateUsing(fn (string $state): string => $statuses[$state] ?? $state),
            TextColumn::make('attempts')->label('محاولات الإرسال'),
            TextColumn::make('failure_code')->label('سبب الفشل')->placeholder('—'),
            TextColumn::make('next_attempt_at')->label('المحاولة التالية')->dateTime('d/m/Y H:i')->timezone('Africa/Tripoli'),
        ])->filters([SelectFilter::make('status')->label('الحالة')->options($statuses)]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageWhatsAppMessages::route('/')];
    }
}
