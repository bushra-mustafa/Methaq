<?php

declare(strict_types=1);

namespace App\Filament\Resources\BankAccounts\Pages;

use App\Domains\Payments\Actions\SaveBankAccountAction;
use App\Domains\Users\Models\User;
use App\Filament\Resources\BankAccounts\BankAccountResource;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

final class ManageBankAccounts extends ManageRecords
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة حساب بنكي')
                ->using(function (array $data): Model {
                    $actor = Filament::auth()->user();
                    abort_unless($actor instanceof User, 403);

                    /** @var array{bank_name: string, account_name: string, account_number?: ?string, iban?: ?string, is_active: bool, sort_order: int} $data */
                    return app(SaveBankAccountAction::class)->execute($actor, $data);
                }),
        ];
    }
}
