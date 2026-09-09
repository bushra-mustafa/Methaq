<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\DTOs\LibraryAssetData;
use App\Domains\Editor\DTOs\LibraryCollectionData;
use App\Domains\Editor\DTOs\LibraryTemplateData;
use App\Domains\Editor\Models\AssetCollection;
use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Models\TemplateAsset;

final class ListAdminLibraryAction
{
    /** @return array{templates: list<array<string, mixed>>, assets: list<array<string, mixed>>, collections: list<array<string, mixed>>} */
    public function execute(): array
    {
        return [
            'templates' => Template::query()
                ->with(['assets' => static fn ($query) => $query->orderByPivot('sort_order')])
                ->orderBy('id')
                ->get()
                ->map(static fn (Template $template): array => LibraryTemplateData::fromModel($template)->toArray())
                ->all(),
            'assets' => TemplateAsset::query()
                ->orderBy('type')
                ->orderBy('id')
                ->get()
                ->map(static fn (TemplateAsset $asset): array => LibraryAssetData::fromModel($asset)->toArray())
                ->all(),
            'collections' => AssetCollection::query()
                ->with(['items' => static fn ($query) => $query->with('asset')->orderBy('sort_order')])
                ->orderBy('id')
                ->get()
                ->map(static fn (AssetCollection $collection): array => LibraryCollectionData::fromModel($collection)->toArray())
                ->all(),
        ];
    }
}
