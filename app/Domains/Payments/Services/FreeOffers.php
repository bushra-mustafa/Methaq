<?php

declare(strict_types=1);

namespace App\Domains\Payments\Services;

use App\Domains\Events\Models\Event;
use App\Domains\Payments\Models\FreeOffer;
use Illuminate\Database\Eloquent\Builder;

/** Finds the free offer a customer can use right now: switched on, inside its window, with a place left. */
final class FreeOffers
{
    /** The live offer that applies to the event (or any live offer when no event is given), the one ending soonest. */
    public function open(?Event $event = null, bool $lock = false): ?FreeOffer
    {
        // Compared as text with microseconds: a Carbon binding would drop them and an offer that starts this very second would look not started.
        $now = now()->format('Y-m-d H:i:s.u');

        return FreeOffer::query()
            ->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>', $now)
            ->whereRaw('(select count(*) from free_offer_claims where free_offer_claims.free_offer_id = free_offers.id) < free_offers.max_claims')
            ->when($event !== null, fn (Builder $query): Builder => $query->where(fn (Builder $scope): Builder => $scope
                ->whereNull('template_id')
                ->when($event?->template_id !== null, fn (Builder $inner): Builder => $inner->orWhere('template_id', $event?->template_id))))
            ->with('template')
            ->orderBy('ends_at')
            ->orderBy('id')
            ->when($lock, fn (Builder $query): Builder => $query->lockForUpdate())
            ->first();
    }

    /** @return array{title: string, description: string|null, spotsTotal: int, spotsLeft: int, endsAt: string, templateName: string|null, ctaHref: string}|null */
    public function forHome(): ?array
    {
        $offer = $this->open();

        return $offer === null ? null : [
            ...$this->summary($offer),
            'templateName' => $offer->template?->name,
            'ctaHref' => $offer->template === null ? '/app/events/create' : '/start/'.$offer->template->slug,
        ];
    }

    /** @return array{title: string, description: string|null, spotsTotal: int, spotsLeft: int, endsAt: string}|null */
    public function forEvent(Event $event): ?array
    {
        $offer = $this->open($event);

        return $offer === null ? null : $this->summary($offer);
    }

    /** @return array{title: string, description: string|null, spotsTotal: int, spotsLeft: int, endsAt: string} */
    private function summary(FreeOffer $offer): array
    {
        return [
            'title' => $offer->title,
            'description' => $offer->description,
            'spotsTotal' => $offer->max_claims,
            'spotsLeft' => max(0, $offer->max_claims - $offer->claims()->count()),
            'endsAt' => $offer->ends_at->toIso8601String(),
        ];
    }
}
