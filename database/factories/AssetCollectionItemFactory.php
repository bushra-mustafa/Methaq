<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Editor\Models\AssetCollectionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssetCollectionItem> */
class AssetCollectionItemFactory extends Factory
{
    protected $model = AssetCollectionItem::class;

    public function definition(): array
    {
        return [
            'collection_id' => AssetCollectionFactory::new(),
            'template_asset_id' => TemplateAssetFactory::new(),
            'sort_order' => 0,
            'placement_json' => ['x' => 0, 'y' => 0, 'width' => 100, 'height' => 100],
        ];
    }
}
