<?php

declare(strict_types=1);

namespace App\Domains\Payments\Models;

use App\Domains\Editor\Models\Template;
use Database\Factories\FreeOfferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A limited free offer: `max_claims` customers may publish for free between `starts_at` and `ends_at`. */
class FreeOffer extends Model
{
    /** @use HasFactory<FreeOfferFactory> */
    use HasFactory;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var list<string> */
    protected $fillable = ['title', 'description', 'template_id', 'max_claims', 'starts_at', 'ends_at', 'is_active'];

    protected static function newFactory(): FreeOfferFactory
    {
        return FreeOfferFactory::new();
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'is_active' => 'boolean',
            'max_claims' => 'integer',
        ];
    }

    /** @return BelongsTo<Template, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /** @return HasMany<FreeOfferClaim, $this> */
    public function claims(): HasMany
    {
        return $this->hasMany(FreeOfferClaim::class);
    }

    /** inactive, scheduled, live, full or ended, from the claims counted by the caller. */
    public function state(int $claimed): string
    {
        $now = now();

        return match (true) {
            ! $this->is_active => 'inactive',
            $this->starts_at->greaterThan($now) => 'scheduled',
            $this->ends_at->lessThanOrEqualTo($now) => 'ended',
            $claimed >= $this->max_claims => 'full',
            default => 'live',
        };
    }
}
