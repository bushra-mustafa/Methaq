<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Editor\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Template> */
class TemplateFactory extends Factory
{
    protected $model = Template::class;

    public function definition(): array
    {
        return [
            'slug' => 'template-'.Str::lower(Str::random(12)),
            'name' => 'Example template',
            'category' => 'wedding',
            'thumbnail_path' => 'templates/preview.png',
            'default_design_json' => ['schemaVersion' => 1, 'width' => 1080, 'height' => 1920, 'layers' => []],
            'default_scene_json' => ['opening' => ['type' => 'direct']],
            'default_palette_json' => ['background' => '#F5F2E3'],
        ];
    }
}
