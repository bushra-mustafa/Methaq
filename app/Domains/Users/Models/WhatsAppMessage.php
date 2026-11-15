<?php

declare(strict_types=1);

namespace App\Domains\Users\Models;

use Illuminate\Database\Eloquent\Model;

final class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = ['deduplication_key', 'user_id', 'recipient', 'body', 'expires_at', 'next_attempt_at'];

    protected $hidden = ['body', 'recipient'];

    protected function casts(): array
    {
        return ['body' => 'encrypted', 'attempts' => 'integer', 'next_attempt_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime'];
    }
}
