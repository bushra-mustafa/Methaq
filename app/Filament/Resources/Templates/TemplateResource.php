<?php

declare(strict_types=1);

namespace App\Filament\Resources\Templates;

use App\Domains\Editor\Actions\UpdateLibraryItemStatusAction;
use App\Domains\Editor\Actions\UpdateTemplateCommerceAction;
use App\Domains\Editor\Actions\UpdateTemplateFeaturedAction;
use App\Domains\Editor\Enums\LibraryResource;
use App\Domains\Editor\Enums\TemplateFeature;
use App\Domains\Editor\Models\Template;
use App\Domains\Events\Enums\EventCategory;
use App\Domains\Payments\Services\LydAmount;
use App\Domains\Users\Models\User;
use App\Filament\Resources\Templates\Pages\ManageTemplates;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Library templates: visibility, home-page featuring, order, price and locked features.
 * Designs stay in TemplateDesignFactory and the editor, so templates are never created,
 * edited or deleted here. Every change goes through a domain Action (policy + audit).
 */
final class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'قالب';

    protected static ?string $pluralModelLabel = 'القوالب';

    protected static ?string $navigationLabel = 'القوالب';

    protected static string|\UnitEnum|null $navigationGroup = 'المحتوى';

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
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->paginated(false)
            ->columns([
                ImageColumn::make('thumbnail_path')
                    ->label('المعاينة')
                    ->state(fn (Template $record): string => asset($record->thumbnail_path))
                    ->imageHeight(72),
                TextColumn::make('name')->label('الاسم')->searchable(),
                TextColumn::make('category')
                    ->label('التصنيف')
                    ->formatStateUsing(fn (EventCategory $state): string => self::categoryLabel($state)),
                TextColumn::make('price_minor')
                    ->label('السعر')
                    ->formatStateUsing(fn (int $state): string => self::formatPrice($state)),
                TextColumn::make('visibility')
                    ->label('العرض للعميلات')
                    ->badge()
                    ->state(fn (Template $record): string => ! $record->is_active ? 'موقوف' : ($record->price_minor === 0 ? 'مخفي: بلا سعر' : 'معروض'))
                    ->color(fn (string $state): string => match ($state) {
                        'معروض' => 'success',
                        'موقوف' => 'gray',
                        default => 'warning',
                    })
                    ->icon(fn (string $state): Heroicon => $state === 'معروض' ? Heroicon::OutlinedEye : Heroicon::OutlinedEyeSlash),
                IconColumn::make('is_active')->label('مفعّل')->boolean(),
                IconColumn::make('is_featured')->label('في الرئيسية')->boolean(),
                TextColumn::make('locked_features')
                    ->label('مميزات مقفلة')
                    ->badge()
                    ->color('warning')
                    ->placeholder('لا شيء'),
            ])
            ->recordActions([
                self::designAction(),
                self::commerceAction(),
                self::visibilityAction(),
                self::featuredAction(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTemplates::route('/'),
        ];
    }

    /** 25000 → «25 د.ل», 25500 → «25.5 د.ل». */
    public static function formatPrice(int $priceMinor): string
    {
        if ($priceMinor === 0) {
            return 'بدون سعر';
        }

        return LydAmount::format($priceMinor);
    }

    private static function designAction(): Action
    {
        return Action::make('design')
            ->label('تصميم')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('primary')
            ->url(fn (Template $record): string => route('app.studio.templates.show', $record));
    }

    private static function commerceAction(): Action
    {
        return Action::make('commerce')
            ->label('السعر والمميزات')
            ->icon(Heroicon::OutlinedBanknotes)
            ->modalHeading(fn (Template $record): string => "السعر والمميزات · {$record->name}")
            ->fillForm(fn (Template $record): array => [
                'price' => $record->price_minor / 1000,
                'locked_features' => $record->locked_features?->map(fn (TemplateFeature $feature): string => $feature->value)->all() ?? [],
            ])
            ->schema([
                TextInput::make('price')
                    ->label('السعر بالدينار الليبي')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(UpdateTemplateCommerceAction::MAX_PRICE_MINOR / 1000)
                    ->step(0.001)
                    ->suffix('د.ل')
                    ->required(),
                CheckboxList::make('locked_features')
                    ->label('مميزات مقفلة في دعوات هذا القالب')
                    ->options(TemplateFeature::class),
            ])
            ->action(function (Template $record, array $data): void {
                app(UpdateTemplateCommerceAction::class)->execute(
                    self::actor(),
                    (int) $record->getKey(),
                    (int) round(((float) $data['price']) * 1000),
                    array_values(array_map(fn (TemplateFeature|string $value): TemplateFeature => $value instanceof TemplateFeature ? $value : TemplateFeature::from($value), $data['locked_features'] ?? [])),
                );
                Notification::make()->success()->title('حُفظ السعر والمميزات')->send();
            });
    }

    private static function visibilityAction(): Action
    {
        return Action::make('visibility')
            ->label(fn (Template $record): string => $record->is_active ? 'إخفاء' : 'إظهار')
            ->icon(fn (Template $record): Heroicon => $record->is_active ? Heroicon::OutlinedEyeSlash : Heroicon::OutlinedEye)
            ->color(fn (Template $record): string => $record->is_active ? 'gray' : 'success')
            ->modalHeading(fn (Template $record): string => ($record->is_active ? 'إخفاء من المكتبة · ' : 'إظهار في المكتبة · ').$record->name)
            ->schema([self::reasonField()])
            ->action(function (Template $record, array $data): void {
                app(UpdateLibraryItemStatusAction::class)->execute(
                    self::actor(),
                    LibraryResource::Template,
                    (int) $record->getKey(),
                    ! $record->is_active,
                    (string) $data['reason'],
                );
                Notification::make()->success()->title('تم تحديث ظهور القالب')->send();
            });
    }

    private static function featuredAction(): Action
    {
        return Action::make('featured')
            ->label(fn (Template $record): string => $record->is_featured ? 'إزالة من الرئيسية' : 'عرض في الرئيسية')
            ->icon(fn (Template $record): Heroicon => $record->is_featured ? Heroicon::OutlinedStar : Heroicon::Star)
            ->color(fn (Template $record): string => $record->is_featured ? 'gray' : 'warning')
            ->modalHeading(fn (Template $record): string => ($record->is_featured ? 'إزالة من الرئيسية · ' : 'عرض في الرئيسية · ').$record->name)
            ->schema([self::reasonField()])
            ->action(function (Template $record, array $data): void {
                app(UpdateTemplateFeaturedAction::class)->execute(
                    self::actor(),
                    (int) $record->getKey(),
                    ! $record->is_featured,
                    (string) $data['reason'],
                );
                Notification::make()->success()->title('تم تحديث قوالب الرئيسية')->send();
            });
    }

    private static function reasonField(): Textarea
    {
        return Textarea::make('reason')->label('السبب (يُحفظ في سجل الإدارة)')->required()->maxLength(500)->rows(2);
    }

    private static function categoryLabel(EventCategory $category): string
    {
        return $category->label();
    }

    private static function actor(): User
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
