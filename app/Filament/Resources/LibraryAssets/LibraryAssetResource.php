<?php

declare(strict_types=1);

namespace App\Filament\Resources\LibraryAssets;

use App\Domains\Editor\Actions\DeleteLibraryAssetAction;
use App\Domains\Editor\Actions\UpdateLibraryItemStatusAction;
use App\Domains\Editor\Enums\AssetType;
use App\Domains\Editor\Enums\LibraryResource;
use App\Domains\Editor\Exceptions\LibraryAssetException;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Users\Models\User;
use App\Filament\Resources\LibraryAssets\Pages\ManageLibraryAssets;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Frames, decorations, icons, backgrounds and fonts designers build with. Only administrators add them; they delete only
 * what they uploaded and nothing a design still uses. Methaq's own elements can be hidden but never deleted.
 */
final class LibraryAssetResource extends Resource
{
    protected static ?string $model = TemplateAsset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $modelLabel = 'عنصر';

    protected static ?string $pluralModelLabel = 'مكتبة العناصر';

    protected static ?string $navigationLabel = 'مكتبة العناصر';

    protected static string|\UnitEnum|null $navigationGroup = 'التشغيل';

    protected static ?int $navigationSort = 8;

    public static function canViewAny(): bool
    {
        return Gate::allows('create', TemplateAsset::class);
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

    /** @return Builder<TemplateAsset> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', '!=', AssetType::Audio->value);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->paginated([25, 50])
            ->filters([
                SelectFilter::make('type')->label('النوع')->options(self::typeLabels()),
            ])
            ->columns([
                ImageColumn::make('preview')
                    ->label('معاينة')
                    ->state(fn (TemplateAsset $record): ?string => $record->type === AssetType::Font ? null : url('/'.ltrim($record->preview_path, '/')))
                    ->height(56),
                TextColumn::make('name')->label('الاسم')->searchable()->weight('bold')->description(fn (TemplateAsset $record): string => $record->type === AssetType::Font ? (string) ($record->metadata['fontFamily'] ?? '') : (string) $record->mime_type),
                TextColumn::make('type')->label('النوع')->badge()->formatStateUsing(fn (AssetType $state): string => self::typeLabels()[$state->value] ?? $state->value),
                TextColumn::make('origin')->label('المصدر')->state(fn (TemplateAsset $record): string => self::isUploaded($record) ? 'مرفوع' : 'أساسي'),
                IconColumn::make('is_active')->label('متاح')->boolean(),
                TextColumn::make('created_at')->label('أُضيف')->dateTime('d/m/Y')->timezone((string) config('methaq.invitation_timezone', 'Africa/Tripoli'))->sortable(),
            ])
            ->recordActions([self::statusAction(), self::deleteAction()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageLibraryAssets::route('/')];
    }

    /** @return array<string, string> */
    private static function typeLabels(): array
    {
        return [
            AssetType::Frame->value => 'إطار',
            AssetType::Decoration->value => 'زخرفة',
            AssetType::Icon->value => 'أيقونة أو ختم',
            AssetType::Background->value => 'خلفية',
            AssetType::Font->value => 'خط',
        ];
    }

    private static function isUploaded(TemplateAsset $asset): bool
    {
        return is_array($asset->license_metadata) && isset($asset->license_metadata['uploadedBy']);
    }

    private static function statusAction(): Action
    {
        return Action::make('status')
            ->label(fn (TemplateAsset $record): string => $record->is_active ? 'إخفاء' : 'إظهار')
            ->icon(fn (TemplateAsset $record) => $record->is_active ? Heroicon::OutlinedEyeSlash : Heroicon::OutlinedEye)
            ->color('gray')
            ->authorize('update')
            ->modalHeading(fn (TemplateAsset $record): string => ($record->is_active ? 'إخفاء من المحرر · ' : 'إظهار في المحرر · ').$record->name)
            ->modalDescription('التصاميم التي تستعمل هذا العنصر تحتفظ به، والإخفاء يمنعه من الاختيار الجديد فقط.')
            ->schema([Textarea::make('reason')->label('السبب')->required()->maxLength(300)])
            ->action(function (TemplateAsset $record, array $data): void {
                app(UpdateLibraryItemStatusAction::class)->execute(self::actor(), LibraryResource::Asset, (int) $record->getKey(), ! $record->is_active, (string) $data['reason']);
                Notification::make()->success()->title('تم تحديث العنصر')->send();
            });
    }

    private static function deleteAction(): Action
    {
        return Action::make('remove')
            ->label('حذف')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->authorize('delete')
            ->visible(fn (TemplateAsset $record): bool => self::isUploaded($record))
            ->requiresConfirmation()
            ->modalHeading(fn (TemplateAsset $record): string => 'حذف '.$record->name)
            ->modalDescription('يُحذف العنصر وملفاته نهائياً. إن كانت تصاميم أو قوالب تستعمله فلن يُحذف، وأخفيه بدلاً من ذلك.')
            ->action(function (TemplateAsset $record): void {
                try {
                    app(DeleteLibraryAssetAction::class)->execute(self::actor(), (int) $record->getKey());
                } catch (LibraryAssetException $exception) {
                    Notification::make()->danger()->title($exception->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title('حُذف العنصر')->send();
            });
    }

    private static function actor(): User
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
