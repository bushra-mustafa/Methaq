<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\DTOs\LibraryCollectionData;
use App\Domains\Editor\DTOs\LibraryTemplateData;
use App\Domains\Editor\Models\AssetCollection;
use App\Domains\Editor\Models\Template;
use App\Domains\Events\Enums\EventCategory;
use Illuminate\Database\Eloquent\Builder;

final class ListTemplateLibraryAction
{
    /** @return array{templates: list<array<string, mixed>>, collections: list<array<string, mixed>>, categories: list<array{value: string, label: string}>} */
    public function execute(?EventCategory $category = null): array
    {
        $templates = Template::query()
            ->where('is_active', true)
            ->when($category, static fn (Builder $query, EventCategory $selected): Builder => $query->where('category', $selected->value))
            ->with(['assets' => static fn ($query) => $query->where('template_assets.is_active', true)->orderByPivot('sort_order')])
            ->orderBy('id')
            ->get()
            ->map(static fn (Template $template): array => LibraryTemplateData::fromModel($template)->toArray())
            ->values()
            ->all();

        $collections = AssetCollection::query()
            ->where('is_active', true)
            ->with(['items' => static fn ($query) => $query
                ->whereHas('asset', static fn ($assetQuery) => $assetQuery->where('is_active', true))
                ->with('asset')
                ->orderBy('sort_order')])
            ->orderBy('id')
            ->get()
            ->filter(static fn (AssetCollection $collection): bool => $collection->items->isNotEmpty())
            ->map(static fn (AssetCollection $collection): array => LibraryCollectionData::fromModel($collection)->toArray())
            ->values()
            ->all();

        return [
            'templates' => $templates,
            'collections' => $collections,
            'categories' => [
                ['value' => EventCategory::Wedding->value, 'label' => 'زفاف'],
                ['value' => EventCategory::Henna->value, 'label' => 'حنّة'],
                ['value' => EventCategory::MarriageContract->value, 'label' => 'عقد قران'],
                ['value' => EventCategory::Graduation->value, 'label' => 'تخرج'],
            ],
        ];
    }
}
