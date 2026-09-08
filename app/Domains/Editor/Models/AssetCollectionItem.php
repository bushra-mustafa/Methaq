<?php

declare(strict_types=1);

namespace App\Domains\Editor\Models;

use Database\Factories\AssetCollectionItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetCollectionItem extends Model
{
    /** @use HasFactory<AssetCollectionItemFactory> */
    use HasFactory;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected static function newFactory(): AssetCollectionItemFactory
    {
        return AssetCollectionItemFactory::new();
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'placement_json' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<AssetCollection, $this> */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(AssetCollection::class, 'collection_id');
    }

    /** @return BelongsTo<TemplateAsset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(TemplateAsset::class, 'template_asset_id');
    }
}
