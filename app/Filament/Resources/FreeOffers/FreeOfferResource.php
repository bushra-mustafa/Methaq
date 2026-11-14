<?php

declare(strict_types=1);

namespace App\Filament\Resources\FreeOffers;

use App\Domains\Editor\Models\Template;
use App\Domains\Payments\Actions\DeleteFreeOfferAction;
use App\Domains\Payments\Actions\SaveFreeOfferAction;
use App\Domains\Payments\Exceptions\PaymentReviewException;
use App\Domains\Payments\Models\FreeOffer;
use App\Domains\Users\Models\User;
use App\Filament\Resources\FreeOffers\Pages\ManageFreeOffers;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Limited free offers: a number of customers may publish without paying inside a window of time. The home page shows
 * the live one. Saving and deleting go through domain Actions; claims are made by customers and only listed here.
 */
final class FreeOfferResource extends Resource
{
    protected static ?string $model = FreeOffer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static ?string $modelLabel = 'عرض مجاني';

    protected static ?string $pluralModelLabel = 'العروض المجانية';

    protected static ?string $navigationLabel = 'العروض المجانية';

    protected static string|\UnitEnum|null $navigationGroup = 'التشغيل';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return Gate::allows('create', FreeOffer::class);
    }

    /** @return Builder<FreeOffer> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('template')->withCount('claims');
    }

    public static function form(Schema $schema): Schema
    {
        $timezone = (string) config('methaq.invitation_timezone', 'Africa/Tripoli');

        return $schema->components([
            TextInput::make('title')->label('عنوان العرض')->placeholder('مثلاً: مجاناً لأول 4 مصمّمات')->required()->maxLength(120),
            Textarea::make('description')->label('وصف قصير يظهر في الصفحة الرئيسية')->rows(2)->maxLength(300),
            Select::make('template_id')->label('ينطبق على')->placeholder('كل القوالب والبطاقة الفارغة')
                ->options(fn (): array => Template::query()->orderBy('name')->pluck('name', 'id')->all())->searchable(),
            TextInput::make('max_claims')->label('عدد المقاعد (العملاء)')->numeric()->minValue(1)->maxValue(1000)->default(4)->required(),
            DateTimePicker::make('starts_at')->label('يبدأ')->seconds(false)->timezone($timezone)->default(now())->required(),
            DateTimePicker::make('ends_at')->label('ينتهي')->seconds(false)->timezone($timezone)->default(now()->addDays(3))->required()->after('starts_at')
                ->helperText('افتراضياً بعد 3 أيام من الآن. حتى 90 يوماً.'),
            Toggle::make('is_active')->label('مفعّل')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        $timezone = (string) config('methaq.invitation_timezone', 'Africa/Tripoli');

        return $table
            ->defaultSort('id', 'desc')
            ->paginated([25])
            ->columns([
                TextColumn::make('title')->label('العرض')->weight('bold')->searchable()
                    ->description(fn (FreeOffer $record): string => $record->template?->name ?? 'كل القوالب والبطاقة الفارغة'),
                TextColumn::make('claims_count')->label('المقاعد')->state(fn (FreeOffer $record): string => $record->claims_count.' / '.$record->max_claims),
                TextColumn::make('starts_at')->label('يبدأ')->dateTime('d/m/Y H:i')->timezone($timezone),
                TextColumn::make('ends_at')->label('ينتهي')->dateTime('d/m/Y H:i')->timezone($timezone),
                TextColumn::make('state')->label('الحالة')->badge()
                    ->state(fn (FreeOffer $record): string => $record->state($record->claims_count))
                    ->formatStateUsing(fn (string $state): string => self::stateLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'live' => 'success',
                        'scheduled' => 'info',
                        'full' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                self::claimsAction(),
                EditAction::make()->using(function (Model $record, array $data): Model {
                    /** @var array{title: string, description?: ?string, template_id?: int|string|null, max_claims: int|string, starts_at: mixed, ends_at: mixed, is_active: bool} $data */
                    return app(SaveFreeOfferAction::class)->execute(self::actor(), $data, (int) $record->getKey());
                }),
                self::deleteAction(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageFreeOffers::route('/')];
    }

    /** @return array<string, string> */
    private static function stateLabels(): array
    {
        return ['live' => 'جارٍ الآن', 'scheduled' => 'لم يبدأ', 'full' => 'اكتملت المقاعد', 'ended' => 'انتهى', 'inactive' => 'متوقف'];
    }

    private static function claimsAction(): Action
    {
        return Action::make('claims')
            ->label('المستفيدون')
            ->icon(Heroicon::OutlinedUsers)
            ->color('gray')
            ->modalHeading(fn (FreeOffer $record): string => 'المستفيدون من «'.$record->title.'»')
            ->modalContent(fn (FreeOffer $record): View => view('filament.offers.claims', ['offer' => $record]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('إغلاق');
    }

    private static function deleteAction(): Action
    {
        return Action::make('remove')
            ->label('حذف')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->authorize('delete')
            ->requiresConfirmation()
            ->modalDescription('يُحذف العرض إن لم يستفد منه أحد. وإن استفاد منه عملاء فأوقفيه بدلاً من حذفه.')
            ->action(function (FreeOffer $record): void {
                try {
                    app(DeleteFreeOfferAction::class)->execute(self::actor(), (int) $record->getKey());
                } catch (PaymentReviewException $exception) {
                    Notification::make()->danger()->title($exception->getMessage())->send();

                    return;
                }
                Notification::make()->success()->title('حُذف العرض')->send();
            });
    }

    private static function actor(): User
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
