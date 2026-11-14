<?php

declare(strict_types=1);

namespace App\Domains\Events\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One invitation's visit counters for one day. Written only by VisitCounter. */
class EventVisitDay extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $table = 'event_visit_days';

    protected function casts(): array
    {
        return ['day' => 'date', 'views' => 'integer', 'visitors' => 'integer'];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
