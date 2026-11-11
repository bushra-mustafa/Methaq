<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\DTOs\LibraryTemplateData;
use App\Domains\Editor\Models\Template;

final class ListFeaturedTemplatesAction
{
    public const LIMIT = 6;

    /** @return list<array<string, mixed>> */
    public function execute(): array
    {
        return Template::query()
            ->where('is_active', true)
            ->with(['assets' => static fn ($query) => $query->where('template_assets.is_active', true)->orderByPivot('sort_order')])
            ->orderBy('id')
            ->limit(self::LIMIT)
            ->get()
            ->map(static fn (Template $template): array => LibraryTemplateData::fromModel($template)->toArray())
            ->values()
            ->all();
    }
}
