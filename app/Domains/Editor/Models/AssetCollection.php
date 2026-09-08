<?php

declare(strict_types=1);

namespace App\Domains\Editor\Models;

use Database\Factories\AssetCollectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCollection extends Model
{
    /** @use HasFactory<AssetCollectionFactory> */
    use HasFactory;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected static function newFactory(): AssetCollectionFactory
    {
        return AssetCollectionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<AssetCollectionItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(AssetCollectionItem::class, 'collection_id');
    }
}
