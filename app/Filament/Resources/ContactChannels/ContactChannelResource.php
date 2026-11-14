<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactChannels;

use App\Domains\Users\Actions\DeleteContactChannelAction;
use App\Domains\Users\Actions\SaveContactChannelAction;
use App\Domains\Users\Enums\ContactIcon;
use App\Domains\Users\Models\ContactChannel;
use App\Domains\Users\Models\User;
use App\Filament\Resources\ContactChannels\Pages\ManageContactChannels;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The ways customers can reach the team (email, phone, social accounts). Active channels appear on the
 * contact page and the footer. Saving and deleting go through domain Actions (policy + audit).
 */
final class ContactChannelResource extends Resource
{
    protected static ?string $model = ContactChannel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAtSymbol;

    protected static ?string $modelLabel = 'وسيلة تواصل';

    protected static ?string $pluralModelLabel = 'وسائل التواصل';

    protected static ?string $navigationLabel = 'وسائل التواصل';

    protected static string|\UnitEnum|null $navigationGroup = 'التشغيل';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('الاسم')->required()->maxLength(80),
            TextInput::make('url')
                ->label('الرابط')
                ->required()
                ->maxLength(255)
                ->helperText('يبدأ بـ https:// أو mailto: أو tel: مثل mailto:info@methq.ly')
                ->regex(ContactChannel::URL_PATTERN)
                ->validationMessages(['regex' => 'الرابط يجب أن يبدأ بـ https:// أو mailto: أو tel:.']),
            Select::make('icon')->label('الأيقونة')->options(ContactIcon::class)->required(),
            TextInput::make('sort_order')->label('الترتيب')->numeric()->minValue(0)->maxValue(1000)->default(0)->required(),
            Toggle::make('is_active')->label('ظاهرة في الموقع')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('الاسم')->weight('bold'),
                TextColumn::make('url')->label('الرابط')->limit(50)->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('icon')->label('الأيقونة')->formatStateUsing(fn (ContactIcon $state): string => $state->getLabel()),
                TextColumn::make('sort_order')->label('الترتيب'),
                IconColumn::make('is_active')->label('ظاهرة')->boolean(),
            ])
            ->recordActions([
                EditAction::make()->using(function (Model $record, array $data): Model {
                    /** @var array{name: string, url: string, icon: string, is_active: bool, sort_order: int} $data */
                    return app(SaveContactChannelAction::class)->execute(self::actor(), $data, (int) $record->getKey());
                }),
                self::deleteAction(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageContactChannels::route('/')];
    }

    private static function deleteAction(): Action
    {
        return DeleteAction::make()
            ->using(function (Model $record): bool {
                app(DeleteContactChannelAction::class)->execute(self::actor(), (int) $record->getKey());

                return true;
            });
    }

    private static function actor(): User
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
