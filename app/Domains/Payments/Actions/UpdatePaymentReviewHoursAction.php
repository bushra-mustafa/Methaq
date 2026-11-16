<?php

declare(strict_types=1);

namespace App\Domains\Payments\Actions;

use App\Domains\Payments\Services\PaymentReviewSettings;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class UpdatePaymentReviewHoursAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function execute(User $actor, int $hours): void
    {
        Gate::forUser($actor)->authorize('manage-payment-settings');
        if ($hours < 1 || $hours > 168) {
            throw ValidationException::withMessages(['hours' => 'اختاري مدة بين ساعة و168 ساعة.']);
        }
        DB::transaction(function () use ($actor, $hours): void {
            DB::table('settings')->updateOrInsert(['key' => PaymentReviewSettings::KEY], ['value' => (string) $hours, 'created_at' => now(), 'updated_at' => now()]);
            $this->audit->record($actor, AuditAction::PaymentReviewHoursUpdated, AuditSubjectType::Setting, 0, metadata: ['hours' => $hours]);
        });
    }
}
