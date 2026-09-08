<?php

declare(strict_types=1);

namespace App\Domains\Editor\Models;

use App\Domains\Events\Models\Event;
use Database\Factories\EventDesignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventDesign extends Model
{
    /** @use HasFactory<EventDesignFactory> */
    use HasFactory;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $hidden = ['design_json', 'scene_json', 'palette_json', 'published_scene_json', 'published_palette_json', 'final_render_path', 'watermarked_preview_path'];

    protected static function newFactory(): EventDesignFactory
    {
        return EventDesignFactory::new();
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'design_json' => 'array',
            'scene_json' => 'array',
            'palette_json' => 'array',
            'published_scene_json' => 'array',
            'published_palette_json' => 'array',
            'schema_version' => 'integer',
            'revision' => 'integer',
            'preview_revision' => 'integer',
            'published_revision' => 'integer',
            'rendered_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return HasMany<DesignRender, $this> */
    public function renders(): HasMany
    {
        return $this->hasMany(DesignRender::class);
    }
}
