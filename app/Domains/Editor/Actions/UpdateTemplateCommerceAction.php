<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\Enums\TemplateFeature;
use App\Domains\Editor\Models\Template;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/** Sets a template's price (LYD minor units) and the features locked for its invitations. */
final class UpdateTemplateCommerceAction
{
    /** Upper bound that keeps a mistyped price from reaching customers (100,000 LYD). */
    public const MAX_PRICE_MINOR = 100_000_000;

    public function __construct(private readonly AuditLogger $auditLogger) {}

    /** @param list<TemplateFeature> $lockedFeatures */
    public function execute(User $actor, int $templateId, int $priceMinor, array $lockedFeatures): void
    {
        if ($priceMinor < 0 || $priceMinor > self::MAX_PRICE_MINOR) {
            throw new InvalidArgumentException('Template price is out of range.');
        }

        $locked = collect($lockedFeatures)->unique()->sortBy(fn (TemplateFeature $feature): string => $feature->value)->values();

        DB::transaction(function () use ($actor, $templateId, $priceMinor, $locked): void {
            $template = Template::query()->lockForUpdate()->findOrFail($templateId);
            Gate::forUser($actor)->authorize('update', $template);

            $previousPrice = $template->price_minor;
            $previousLocked = $template->locked_features?->map(fn (TemplateFeature $feature): string => $feature->value)->sort()->values()->all() ?? [];
            $newLocked = $locked->map(fn (TemplateFeature $feature): string => $feature->value)->all();

            if ($previousPrice === $priceMinor && $previousLocked === $newLocked) {
                return;
            }

            $template->forceFill(['price_minor' => $priceMinor, 'locked_features' => $locked->all()])->save();
            $this->auditLogger->record(
                actor: $actor,
                action: AuditAction::TemplateCommerceUpdated,
                subjectType: AuditSubjectType::Template,
                subjectId: (int) $template->getKey(),
                reason: null,
                metadata: [
                    'previousPriceMinor' => $previousPrice,
                    'priceMinor' => $priceMinor,
                    'previousLockedFeatures' => $previousLocked,
                    'lockedFeatures' => $newLocked,
                ],
            );
        });
    }
}
