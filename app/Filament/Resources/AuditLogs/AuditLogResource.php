<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuditLogs;

use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Models\AuditLog;
use App\Filament\Resources\AuditLogs\Pages\ManageAuditLogs;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Who decided what and when: every approval, rejection, publication, suspension and library change. Read only. */
final class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'سجل';

    protected static ?string $pluralModelLabel = 'سجل التدقيق';

    protected static ?string $navigationLabel = 'سجل التدقيق';

    protected static string|\UnitEnum|null $navigationGroup = 'النظام';

    protected static ?int $navigationSort = 9;

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

    /** @return Builder<AuditLog> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('actor');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('created_at')
                    ->label('الوقت')
                    ->dateTime('d/m/Y H:i')
                    ->timezone((string) config('methaq.invitation_timezone', 'Africa/Tripoli'))
                    ->sortable(),
                TextColumn::make('action')
                    ->label('الإجراء')
                    ->badge()
                    ->state(fn (AuditLog $record): string => self::actionLabel((string) $record->action))
                    ->color(fn (AuditLog $record): string => self::actionColor((string) $record->action))
                    ->searchable(),
                TextColumn::make('actor.name')->label('بواسطة')->placeholder('النظام')->icon(Heroicon::OutlinedUser),
                TextColumn::make('subject')
                    ->label('على')
                    ->state(fn (AuditLog $record): string => self::subjectLabel((string) $record->subject_type).' #'.$record->subject_id),
                TextColumn::make('reason')->label('السبب')->placeholder('—')->wrap()->limit(80),
            ])
            ->filters([
                SelectFilter::make('action')->label('الإجراء')->options(self::actionOptions()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAuditLogs::route('/')];
    }

    /** @return array<string, string> */
    private static function actionOptions(): array
    {
        $options = [];
        foreach (AuditAction::cases() as $case) {
            $options[$case->value] = self::actionLabel($case->value);
        }

        return $options;
    }

    private static function actionLabel(string $action): string
    {
        return match ($action) {
            AuditAction::PaymentApproved->value => 'اعتماد دفع',
            AuditAction::PaymentRejected->value => 'رفض قسيمة',
            AuditAction::EventPublished->value => 'نشر دعوة',
            AuditAction::EventSuspended->value => 'إيقاف دعوة',
            AuditAction::EventReinstated->value => 'إعادة تفعيل دعوة',
            AuditAction::UserSuspended->value => 'إيقاف حساب',
            AuditAction::UserReinstated->value => 'إعادة تفعيل حساب',
            AuditAction::UserPromotedToAdmin->value => 'ترقية لمديرة',
            AuditAction::TemplateActivated->value => 'تفعيل قالب',
            AuditAction::TemplateDeactivated->value => 'إيقاف قالب',
            AuditAction::TemplateFeatured->value => 'عرض قالب في الرئيسية',
            AuditAction::TemplateUnfeatured->value => 'إزالة قالب من الرئيسية',
            AuditAction::TemplateCommerceUpdated->value => 'تعديل سعر أو ميزات قالب',
            AuditAction::TemplateCreated->value => 'إنشاء قالب جديد',
            AuditAction::TemplateDesigned->value => 'تعديل تصميم قالب',
            AuditAction::TemplateThumbnailUpdated->value => 'تحديث صورة معاينة قالب',
            AuditAction::TemplateAssetActivated->value => 'تفعيل عنصر',
            AuditAction::TemplateAssetDeactivated->value => 'إيقاف عنصر',
            AuditAction::AssetCollectionActivated->value => 'تفعيل مجموعة',
            AuditAction::AssetCollectionDeactivated->value => 'إيقاف مجموعة',
            default => $action,
        };
    }

    private static function actionColor(string $action): string
    {
        return match (true) {
            in_array($action, [AuditAction::PaymentApproved->value, AuditAction::EventPublished->value, AuditAction::UserReinstated->value, AuditAction::EventReinstated->value], true) => 'success',
            in_array($action, [AuditAction::PaymentRejected->value, AuditAction::UserSuspended->value, AuditAction::EventSuspended->value], true) => 'danger',
            str_starts_with($action, 'template') || str_starts_with($action, 'asset_collection') => 'info',
            default => 'gray',
        };
    }

    private static function subjectLabel(string $type): string
    {
        return match ($type) {
            'user' => 'مستخدمة',
            'event' => 'مناسبة',
            'order' => 'طلب',
            'template' => 'قالب',
            'template_asset' => 'عنصر',
            'asset_collection' => 'مجموعة',
            default => $type,
        };
    }
}
