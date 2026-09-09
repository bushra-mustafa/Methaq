<?php

declare(strict_types=1);

namespace App\Domains\Editor\Models;

use App\Domains\Editor\Enums\AssetType;
use Database\Factories\TemplateAssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateAsset extends Model
{
    /** @use HasFactory<TemplateAssetFactory> */
    use HasFactory;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var array<string, int|bool> */
    protected $attributes = [
        'asset_version' => 1,
        'is_active' => true,
    ];

    protected $hidden = ['original_path'];

    protected static function newFactory(): TemplateAssetFactory
    {
        return TemplateAssetFactory::new();
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'type' => AssetType::class,
            'metadata' => 'array',
            'capabilities' => 'array',
            'license_metadata' => 'array',
            'asset_version' => 'integer',
            'is_active' => 'boolean',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /** @return BelongsToMany<Template, $this> */
    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(Template::class, 'template_asset_links', 'template_asset_id', 'template_id')->withPivot('sort_order');
    }

    /** @return HasMany<AssetCollectionItem, $this> */
    public function collectionItems(): HasMany
    {
        return $this->hasMany(AssetCollectionItem::class);
    }
}
