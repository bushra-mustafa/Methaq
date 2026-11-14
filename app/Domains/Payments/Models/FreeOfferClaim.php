<?php

declare(strict_types=1);

namespace App\Domains\Payments\Models;

use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One customer's use of a free offer, tied to the event it activated. Written only by ClaimFreeOfferAction. */
class FreeOfferClaim extends Model
{
    public const UPDATED_AT = null;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<FreeOffer, $this> */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(FreeOffer::class, 'free_offer_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
