<?php

declare(strict_types=1);

namespace App\Domains\Payments\Actions;

use App\Domains\Payments\Models\BankAccount;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * An administrator adds or edits a receiving bank account. Customers pay into whatever this holds, so every
 * change is audited with a masked number (never the full one), and the account needs a number or an IBAN.
 */
final class SaveBankAccountAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /** @param array{bank_name: string, account_name: string, account_number?: ?string, iban?: ?string, is_active: bool, sort_order: int} $data */
    public function execute(User $actor, array $data, ?int $accountId = null): BankAccount
    {
        $bank = trim($data['bank_name']);
        $holder = trim($data['account_name']);
        $number = $this->clean($data['account_number'] ?? null);
        $iban = $this->clean($data['iban'] ?? null);

        if ($bank === '' || mb_strlen($bank) > 80) {
            throw ValidationException::withMessages(['bank_name' => 'اكتبي اسم المصرف (حتى 80 حرفاً).']);
        }
        if ($holder === '' || mb_strlen($holder) > 120) {
            throw ValidationException::withMessages(['account_name' => 'اكتبي اسم صاحب الحساب (حتى 120 حرفاً).']);
        }
        if ($number === null && $iban === null) {
            throw ValidationException::withMessages(['account_number' => 'أدخلي رقم الحساب أو IBAN.']);
        }
        if ($number !== null && preg_match(BankAccount::NUMBER_PATTERN, $number) !== 1) {
            throw ValidationException::withMessages(['account_number' => 'رقم الحساب أرقام فقط (يُسمح بمسافات وشرطات).']);
        }
        if ($iban !== null && preg_match(BankAccount::IBAN_PATTERN, $iban) !== 1) {
            throw ValidationException::withMessages(['iban' => 'صيغة IBAN غير صحيحة.']);
        }

        return DB::transaction(function () use ($actor, $data, $bank, $holder, $number, $iban, $accountId): BankAccount {
            $account = $accountId === null ? new BankAccount : BankAccount::query()->lockForUpdate()->findOrFail($accountId);
            Gate::forUser($actor)->authorize($accountId === null ? 'create' : 'update', $accountId === null ? BankAccount::class : $account);

            $account->fill([
                'bank_name' => $bank,
                'account_name' => $holder,
                'account_number' => $number,
                'iban' => $iban === null ? null : strtoupper($iban),
                'is_active' => $data['is_active'],
                'sort_order' => $data['sort_order'],
            ])->save();

            $this->auditLogger->record(
                actor: $actor,
                action: AuditAction::BankAccountSaved,
                subjectType: AuditSubjectType::BankAccount,
                subjectId: (int) $account->getKey(),
                metadata: ['bank' => $bank, 'last4' => $this->lastFour($number ?? $iban), 'active' => $account->is_active, 'created' => $accountId === null],
            );

            return $account;
        });
    }

    private function clean(?string $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function lastFour(?string $value): string
    {
        return $value === null ? '' : substr(preg_replace('/\s+/', '', $value) ?? '', -4);
    }
}
