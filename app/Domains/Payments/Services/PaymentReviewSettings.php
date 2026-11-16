<?php

declare(strict_types=1);

namespace App\Domains\Payments\Services;

use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Enums\PaymentGateway;
use App\Domains\Payments\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class PaymentReviewSettings
{
    public const KEY = 'payments.review_reminder_hours';

    public function hours(): int
    {
        $hours = DB::table('settings')->where('key', self::KEY)->value('value');

        return is_numeric($hours) ? max(1, min(168, (int) $hours)) : 24;
    }

    public function overdue(): Builder
    {
        return Order::query()->where('gateway', PaymentGateway::ManualTransfer->value)
            ->where('status', OrderStatus::Pending->value)->where('created_at', '<=', now()->subHours($this->hours()));
    }
}
