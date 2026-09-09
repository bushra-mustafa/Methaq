<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Editor\Models\AssetCollection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AssetCollection> */
class AssetCollectionFactory extends Factory
{
    protected $model = AssetCollection::class;

    public function definition(): array
    {
        return [
            'slug' => 'collection-'.Str::lower(Str::random(12)),
            'name' => 'Example collection',
        ];
    }
}
