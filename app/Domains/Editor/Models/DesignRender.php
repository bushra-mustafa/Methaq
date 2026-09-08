<?php

declare(strict_types=1);

namespace App\Domains\Editor\Models;

use App\Domains\Editor\Enums\RenderKind;
use App\Domains\Editor\Enums\RenderStatus;
use Database\Factories\DesignRenderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignRender extends Model
{
    /** @use HasFactory<DesignRenderFactory> */
    use HasFactory;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $hidden = ['design_snapshot', 'output_path'];

    protected static function newFactory(): DesignRenderFactory
    {
        return DesignRenderFactory::new();
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'kind' => RenderKind::class,
            'status' => RenderStatus::class,
            'design_snapshot' => 'array',
            'revision' => 'integer',
            'attempts' => 'integer',
            'available_at' => 'immutable_datetime',
            'locked_until' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<EventDesign, $this> */
    public function design(): BelongsTo
    {
        return $this->belongsTo(EventDesign::class, 'event_design_id');
    }
}
