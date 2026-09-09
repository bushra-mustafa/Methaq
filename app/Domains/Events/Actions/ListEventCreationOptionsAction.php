<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Editor\Models\Template;
use App\Domains\Events\Enums\EventCategory;

final class ListEventCreationOptionsAction
{
    /** @return array{templates: list<array{id: string, slug: string, name: string, category: string, thumbnailUrl: string}>, categories: list<array{value: string, label: string}>, timezones: list<array{value: string, label: string}>, selectedTemplateSlug: string|null} */
    public function execute(?string $selectedTemplateSlug): array
    {
        $templates = Template::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(static fn (Template $template): array => [
                'id' => (string) $template->getKey(),
                'slug' => $template->slug,
                'name' => $template->name,
                'category' => $template->category->value,
                'thumbnailUrl' => '/'.ltrim($template->thumbnail_path, '/'),
            ])
            ->all();

        return [
            'templates' => $templates,
            'categories' => [
                ['value' => EventCategory::Wedding->value, 'label' => 'زفاف'],
                ['value' => EventCategory::Henna->value, 'label' => 'حنّة'],
                ['value' => EventCategory::MarriageContract->value, 'label' => 'عقد قران'],
                ['value' => EventCategory::Graduation->value, 'label' => 'تخرج'],
            ],
            'timezones' => [
                ['value' => 'Africa/Tripoli', 'label' => 'طرابلس'],
                ['value' => 'Africa/Cairo', 'label' => 'القاهرة'],
                ['value' => 'Asia/Riyadh', 'label' => 'الرياض'],
                ['value' => 'Asia/Dubai', 'label' => 'دبي'],
                ['value' => 'Europe/London', 'label' => 'لندن'],
            ],
            'selectedTemplateSlug' => $selectedTemplateSlug,
        ];
    }
}
