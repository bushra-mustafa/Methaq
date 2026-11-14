<?php

declare(strict_types=1);

namespace App\Filament\Resources\BankAccounts;

use App\Domains\Payments\Actions\DeleteBankAccountAction;
use App\Domains\Payments\Actions\SaveBankAccountAction;
use App\Domains\Payments\Models\BankAccount;
use App\Domains\Users\Models\User;
use App\Filament\Resources\BankAccounts\Pages\ManageBankAccounts;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * The bank accounts customers transfer to. The payment page shows every active account in this order; while
 * none is added it falls back to the server settings (METHAQ_BANK_*). Saving and deleting go through domain
 * Actions (policy + audit with a masked number).
 */
final class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $modelLabel = 'حساب بنكي';

    protected static ?string $pluralModelLabel = 'الحسابات البنكية';

    protected static ?string $navigationLabel = 'الحسابات البنكية';

    protected static string|\UnitEnum|null $navigationGroup = 'التشغيل';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Gate::allows('create', BankAccount::class);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('bank_name')->label('المصرف')->required()->maxLength(80),
            TextInput::make('account_name')->label('اسم صاحب الحساب')->required()->maxLength(120),
            TextInput::make('account_number')->label('رقم الحساب')->maxLength(40)->extraInputAttributes(['dir' => 'ltr'])
                ->requiredWithout('iban')
                ->regex(BankAccount::NUMBER_PATTERN)
                ->validationMessages(['regex' => 'أرقام فقط (يُسمح بمسافات وشرطات).', 'required_without' => 'أدخلي رقم الحساب أو IBAN.']),
            TextInput::make('iban')->label('IBAN')->maxLength(40)->extraInputAttributes(['dir' => 'ltr'])
                ->requiredWithout('account_number')
                ->regex(BankAccount::IBAN_PATTERN)
                ->validationMessages(['regex' => 'صيغة IBAN غير صحيحة.', 'required_without' => 'أدخلي رقم الحساب أو IBAN.']),
            TextInput::make('sort_order')->label('الترتيب')->numeric()->minValue(0)->maxValue(1000)->default(0)->required(),
            Toggle::make('is_active')->label('ظاهر للعميلات في صفحة الدفع')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->paginated(false)
            ->columns([
                TextColumn::make('bank_name')->label('المصرف')->weight('bold'),
                TextColumn::make('account_name')->label('اسم الحساب'),
                TextColumn::make('account_number')->label('رقم الحساب')->placeholder('—')->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('iban')->label('IBAN')->placeholder('—')->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('sort_order')->label('الترتيب'),
                IconColumn::make('is_active')->label('ظاهر')->boolean(),
            ])
            ->recordActions([
                EditAction::make()->using(function (Model $record, array $data): Model {
                    /** @var array{bank_name: string, account_name: string, account_number?: ?string, iban?: ?string, is_active: bool, sort_order: int} $data */
                    return app(SaveBankAccountAction::class)->execute(self::actor(), $data, (int) $record->getKey());
                }),
                self::deleteAction(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageBankAccounts::route('/')];
    }

    private static function deleteAction(): Action
    {
        return DeleteAction::make()
            ->modalDescription('إن لم يبقَ حساب نشط، يعود موقع الدفع إلى إعدادات الخادم (METHAQ_BANK_*)، وإن لم تكن مضبوطة تُقفل صفحة الدفع.')
            ->using(function (Model $record): bool {
                app(DeleteBankAccountAction::class)->execute(self::actor(), (int) $record->getKey());

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
