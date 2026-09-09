<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Editor\Models\TemplateAsset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<TemplateAsset> */
class TemplateAssetFactory extends Factory
{
    protected $model = TemplateAsset::class;

    public function definition(): array
    {
        return [
            'slug' => 'asset-'.Str::lower(Str::random(12)),
            'name' => 'Example frame',
            'type' => 'frame',
            'original_path' => 'assets/frame.svg',
            'preview_path' => 'previews/frame.png',
            'mime_type' => 'image/svg+xml',
            'width' => 1080,
            'height' => 1920,
            'capabilities' => ['supportsTint' => true],
        ];
    }
}
