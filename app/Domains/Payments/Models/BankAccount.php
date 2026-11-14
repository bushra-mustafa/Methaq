<?php

declare(strict_types=1);

namespace App\Domains\Payments\Models;

use Database\Factories\BankAccountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** A bank account customers transfer to. Only administrators change it; the payment page shows the active ones. */
class BankAccount extends Model
{
    /** @use HasFactory<BankAccountFactory> */
    use HasFactory;

    /** Digits, spaces and dashes only. */
    public const NUMBER_PATTERN = '/^[0-9][0-9 \-]{3,38}[0-9]$/';

    /** Two letters, then letters, digits and spaces. */
    public const IBAN_PATTERN = '/^[A-Za-z]{2}[0-9A-Za-z ]{8,36}$/';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var list<string> */
    protected $fillable = ['bank_name', 'account_name', 'account_number', 'iban', 'is_active', 'sort_order'];

    protected static function newFactory(): BankAccountFactory
    {
        return BankAccountFactory::new();
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<BankAccount>  $query
     * @return Builder<BankAccount>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}
