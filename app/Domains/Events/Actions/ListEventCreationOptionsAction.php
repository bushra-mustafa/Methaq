<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Editor\Models\Template;
use App\Domains\Events\Enums\EventCategory;
use App\Domains\Events\Services\InvitationExpiry;
use App\Domains\Payments\Services\FreeOffers;
use App\Domains\Payments\Services\LydAmount;
use App\Domains\Payments\Services\PricingSettings;

final class ListEventCreationOptionsAction
{
    public function __construct(private readonly InvitationExpiry $expiry, private readonly PricingSettings $pricing, private readonly FreeOffers $offers) {}

    /** @return array{freeOffer: array{title: string, description: string|null, spotsTotal: int, spotsLeft: int, endsAt: string, templateName: string|null, ctaHref: string}|null, blankCard: array{available: bool, priceLabel: string|null}, templates: list<array{id: string, slug: string, name: string, category: string, thumbnailUrl: string, priceLabel: string}>, categories: list<array{value: string, label: string}>, timezones: list<array{value: string, label: string}>, selectedTemplateSlug: string|null, graceDays: int} */
    public function execute(?string $selectedTemplateSlug): array
    {
        $templates = Template::query()
            ->onSale()
            ->libraryOrder()
            ->get()
            ->map(static fn (Template $template): array => [
                'id' => (string) $template->getKey(),
                'slug' => $template->slug,
                'name' => $template->name,
                'category' => $template->category->value,
                'thumbnailUrl' => '/'.ltrim($template->thumbnail_path, '/'),
                'priceLabel' => LydAmount::format($template->price_minor),
            ])
            ->all();

        return [
            'templates' => $templates,
            'categories' => EventCategory::options(),
            'timezones' => [
                ['value' => 'Africa/Tripoli', 'label' => 'طرابلس'],
                ['value' => 'Africa/Cairo', 'label' => 'القاهرة'],
                ['value' => 'Asia/Riyadh', 'label' => 'الرياض'],
                ['value' => 'Asia/Dubai', 'label' => 'دبي'],
                ['value' => 'Europe/London', 'label' => 'لندن'],
            ],
            'blankCard' => ['available' => $this->pricing->blankCardPriceMinor() > 0, 'priceLabel' => $this->pricing->blankCardPriceMinor() > 0 ? LydAmount::format($this->pricing->blankCardPriceMinor()) : null],
            'freeOffer' => $this->offers->forHome(),
            'selectedTemplateSlug' => $selectedTemplateSlug,
            'graceDays' => $this->expiry->graceDays(),
        ];
    }
}
