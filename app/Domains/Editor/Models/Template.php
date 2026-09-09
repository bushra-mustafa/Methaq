<?php

declare(strict_types=1);

namespace App\Domains\Editor\Models;

use App\Domains\Events\Enums\EventCategory;
use App\Domains\Events\Models\Event;
use Database\Factories\TemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    /** @use HasFactory<TemplateFactory> */
    use HasFactory;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var array<string, int|bool> */
    protected $attributes = [
        'schema_version' => 1,
        'is_active' => true,
    ];

    protected $hidden = ['default_design_json', 'default_scene_json', 'default_palette_json'];

    protected static function newFactory(): TemplateFactory
    {
        return TemplateFactory::new();
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'category' => EventCategory::class,
            'default_design_json' => 'array',
            'default_scene_json' => 'array',
            'default_palette_json' => 'array',
            'schema_version' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Event, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /** @return BelongsToMany<TemplateAsset, $this> */
    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(TemplateAsset::class, 'template_asset_links', 'template_id', 'template_asset_id')->withPivot('sort_order');
    }
}
