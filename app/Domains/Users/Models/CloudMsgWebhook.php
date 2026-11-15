<?php

declare(strict_types=1);

namespace App\Domains\Users\Models;

use Illuminate\Database\Eloquent\Model;

final class CloudMsgWebhook extends Model
{
    protected $fillable = ['provider_event_id', 'payload'];

    protected $hidden = ['payload'];

    protected function casts(): array
    {
        return ['payload' => 'encrypted:array', 'attempts' => 'integer', 'processed_at' => 'immutable_datetime', 'next_attempt_at' => 'immutable_datetime'];
    }
}
