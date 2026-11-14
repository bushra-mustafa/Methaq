<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Payments\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BankAccount> */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'bank_name' => 'Example Bank',
            'account_name' => 'Example Account',
            'account_number' => '0012345678',
            'iban' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
