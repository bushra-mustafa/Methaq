<?php

declare(strict_types=1);

namespace App\Domains\Users\Models;

use Database\Factories\SupportMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    /** @use HasFactory<SupportMessageFactory> */
    use HasFactory;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var list<string> */
    protected $fillable = ['support_thread_id', 'author_id', 'from_admin', 'body'];

    protected static function newFactory(): SupportMessageFactory
    {
        return SupportMessageFactory::new();
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'from_admin' => 'boolean',
        ];
    }

    /** @return BelongsTo<SupportThread, $this> */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(SupportThread::class, 'support_thread_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
