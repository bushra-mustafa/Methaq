<?php

declare(strict_types=1);

namespace App\Domains\Events\Models;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Models\Template;
use App\Domains\Events\Enums\EventCategory;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Payments\Models\Order;
use App\Domains\RSVP\Models\Rsvp;
use App\Domains\Users\Models\User;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected static function newFactory(): EventFactory
    {
        return EventFactory::new();
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'category' => EventCategory::class,
            'status' => EventStatus::class,
            'is_paid' => 'boolean',
            'event_date' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'suspended_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Template, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /** @return HasOne<EventDesign, $this> */
    public function design(): HasOne
    {
        return $this->hasOne(EventDesign::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<Rsvp, $this> */
    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }
}
