<?php

declare(strict_types=1);

namespace App\Domains\Payments\Actions;

use App\Domains\Editor\Models\Template;
use App\Domains\Payments\Models\FreeOffer;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** An administrator creates or edits a limited free offer. The number of places cannot drop below the places already taken. */
final class SaveFreeOfferAction
{
    public const MAX_WINDOW_DAYS = 90;

    public function __construct(private readonly AuditLogger $auditLogger) {}

    /** @param array{title: string, description?: ?string, template_id?: int|string|null, max_claims: int|string, starts_at: mixed, ends_at: mixed, is_active: bool} $data */
    public function execute(User $actor, array $data, ?int $offerId = null): FreeOffer
    {
        $title = trim($data['title']);
        $description = trim((string) ($data['description'] ?? ''));
        $places = (int) $data['max_claims'];
        $starts = CarbonImmutable::parse($data['starts_at']);
        $ends = CarbonImmutable::parse($data['ends_at']);
        $templateId = ($data['template_id'] ?? null) === null || $data['template_id'] === '' ? null : (int) $data['template_id'];

        if ($title === '' || mb_strlen($title) > 120) {
            throw ValidationException::withMessages(['title' => 'اكتبي عنواناً للعرض (حتى 120 حرفاً).']);
        }
        if (mb_strlen($description) > 300) {
            throw ValidationException::withMessages(['description' => 'الوصف يجب ألا يتجاوز 300 حرف.']);
        }
        if ($places < 1 || $places > 1000) {
            throw ValidationException::withMessages(['max_claims' => 'عدد المقاعد بين 1 و1000.']);
        }
        if ($ends->lessThanOrEqualTo($starts) || $starts->diffInDays($ends) > self::MAX_WINDOW_DAYS) {
            throw ValidationException::withMessages(['ends_at' => 'نهاية العرض بعد بدايته، وبحد أقصى '.self::MAX_WINDOW_DAYS.' يوماً.']);
        }
        if ($templateId !== null && ! Template::query()->whereKey($templateId)->exists()) {
            throw ValidationException::withMessages(['template_id' => 'القالب غير موجود.']);
        }

        return DB::transaction(function () use ($actor, $data, $title, $description, $places, $starts, $ends, $templateId, $offerId): FreeOffer {
            $offer = $offerId === null ? new FreeOffer : FreeOffer::query()->lockForUpdate()->findOrFail($offerId);
            Gate::forUser($actor)->authorize($offerId === null ? 'create' : 'update', $offerId === null ? FreeOffer::class : $offer);

            if ($offerId !== null && $places < $offer->claims()->count()) {
                throw ValidationException::withMessages(['max_claims' => 'لا يمكن أن تقل المقاعد عن '.$offer->claims()->count().' مقعداً أُخذت فعلاً.']);
            }

            $offer->fill([
                'title' => $title,
                'description' => $description === '' ? null : $description,
                'template_id' => $templateId,
                'max_claims' => $places,
                'starts_at' => $starts,
                'ends_at' => $ends,
                'is_active' => $data['is_active'],
            ])->save();

            $this->auditLogger->record(
                actor: $actor,
                action: AuditAction::FreeOfferSaved,
                subjectType: AuditSubjectType::FreeOffer,
                subjectId: (int) $offer->getKey(),
                metadata: ['title' => $title, 'places' => $places, 'active' => $offer->is_active, 'created' => $offerId === null],
            );

            return $offer;
        });
    }
}
