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

final class DeleteBankAccountAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(User $actor, int $accountId): void
    {
        DB::transaction(function () use ($actor, $accountId): void {
            $account = BankAccount::query()->lockForUpdate()->findOrFail($accountId);
            Gate::forUser($actor)->authorize('delete', $account);

            $this->auditLogger->record(
                actor: $actor,
                action: AuditAction::BankAccountDeleted,
                subjectType: AuditSubjectType::BankAccount,
                subjectId: $accountId,
                metadata: ['bank' => $account->bank_name, 'last4' => substr(preg_replace('/\s+/', '', (string) ($account->account_number ?? $account->iban)) ?? '', -4)],
            );
            $account->delete();
        });
    }
}
