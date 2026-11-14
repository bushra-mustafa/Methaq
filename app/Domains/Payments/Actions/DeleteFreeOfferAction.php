<?php

declare(strict_types=1);

namespace App\Domains\Payments\Actions;

use App\Domains\Payments\Exceptions\PaymentReviewException;
use App\Domains\Payments\Models\FreeOffer;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Deletes an offer nobody used. One with claims is switched off instead, so its record stays. */
final class DeleteFreeOfferAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(User $actor, int $offerId): void
    {
        DB::transaction(function () use ($actor, $offerId): void {
            $offer = FreeOffer::query()->lockForUpdate()->findOrFail($offerId);
            Gate::forUser($actor)->authorize('delete', $offer);

            if ($offer->claims()->exists()) {
                throw new PaymentReviewException('استفاد من هذا العرض عملاء، فلا يُحذف. أوقفيه بدلاً من ذلك.');
            }

            $this->auditLogger->record($actor, AuditAction::FreeOfferDeleted, AuditSubjectType::FreeOffer, $offerId, metadata: ['title' => $offer->title]);
            $offer->delete();
        });
    }
}
