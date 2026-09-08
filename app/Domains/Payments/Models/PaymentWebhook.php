<?php

declare(strict_types=1);

namespace App\Domains\Payments\Models;

use App\Domains\Payments\Enums\PaymentGateway;
use App\Domains\Payments\Enums\WebhookStatus;
use Database\Factories\PaymentWebhookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhook extends Model
{
    /** @use HasFactory<PaymentWebhookFactory> */
    use HasFactory;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $hidden = ['payload'];

    protected static function newFactory(): PaymentWebhookFactory
    {
        return PaymentWebhookFactory::new();
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'gateway' => PaymentGateway::class,
            'status' => WebhookStatus::class,
            'payload' => 'array',
            'attempts' => 'integer',
            'received_at' => 'immutable_datetime',
            'processed_at' => 'immutable_datetime',
            'locked_until' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
