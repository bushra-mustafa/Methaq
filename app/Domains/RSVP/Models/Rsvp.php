<?php

declare(strict_types=1);

namespace App\Domains\RSVP\Models;

use App\Domains\Events\Models\Event;
use App\Domains\RSVP\Enums\AttendingStatus;
use Database\Factories\RsvpFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rsvp extends Model
{
    /** @use HasFactory<RsvpFactory> */
    use HasFactory;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $hidden = ['guest_phone', 'notes', 'submission_token'];

    protected static function newFactory(): RsvpFactory
    {
        return RsvpFactory::new();
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'attending_status' => AttendingStatus::class,
            'companions_count' => 'integer',
        ];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
