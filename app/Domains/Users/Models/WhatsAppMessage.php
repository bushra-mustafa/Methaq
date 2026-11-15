<?php

declare(strict_types=1);

namespace App\Domains\Users\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = ['deduplication_key', 'user_id', 'recipient', 'body', 'expires_at', 'next_attempt_at', 'kind', 'resend_of_id'];

    protected $hidden = ['body', 'recipient'];

    public function source(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resend_of_id');
    }

    public function resends(): HasMany
    {
        return $this->hasMany(self::class, 'resend_of_id');
    }

    protected function casts(): array
    {
        return ['body' => 'encrypted', 'attempts' => 'integer', 'next_attempt_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime'];
    }
}
