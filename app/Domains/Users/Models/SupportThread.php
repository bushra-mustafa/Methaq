<?php

declare(strict_types=1);

namespace App\Domains\Users\Models;

use App\Domains\Users\Enums\SupportThreadStatus;
use App\Domains\Users\Enums\SupportTopic;
use Database\Factories\SupportThreadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One conversation between a customer and the team: a problem report or a custom-design request. */
class SupportThread extends Model
{
    /** @use HasFactory<SupportThreadFactory> */
    use HasFactory;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var list<string> */
    protected $fillable = ['user_id', 'topic', 'subject', 'status', 'admin_unread', 'customer_unread', 'last_message_at'];

    protected static function newFactory(): SupportThreadFactory
    {
        return SupportThreadFactory::new();
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'last_message_at' => 'immutable_datetime',
            'topic' => SupportTopic::class,
            'status' => SupportThreadStatus::class,
            'admin_unread' => 'boolean',
            'customer_unread' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<SupportMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at')->orderBy('id');
    }
}
