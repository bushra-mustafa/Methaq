<?php

declare(strict_types=1);

namespace App\Domains\Payments\Actions;

use App\Domains\Payments\Services\PricingSettings;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** An administrator sets what starting from a blank card costs. Zero stops offering the blank card. Audited. */
final class UpdateBlankCardPriceAction
{
    public function __construct(private readonly PricingSettings $pricing, private readonly AuditLogger $auditLogger) {}

    public function execute(User $actor, int $priceMinor): void
    {
        Gate::forUser($actor)->authorize('manage-pricing');

        if ($priceMinor < 0 || $priceMinor > PricingSettings::MAXIMUM_MINOR) {
            throw ValidationException::withMessages(['price' => 'السعر يجب أن يكون بين 0 و100000 د.ل.']);
        }

        DB::transaction(function () use ($actor, $priceMinor): void {
            $previous = $this->pricing->blankCardPriceMinor();
            $this->pricing->setBlankCardPriceMinor($priceMinor);

            if ($previous !== $priceMinor) {
                $this->auditLogger->record(
                    actor: $actor,
                    action: AuditAction::BlankCardPriceUpdated,
                    subjectType: AuditSubjectType::Setting,
                    subjectId: 0,
                    metadata: ['previousMinor' => $previous, 'newMinor' => $priceMinor],
                );
            }
        });
    }
}
