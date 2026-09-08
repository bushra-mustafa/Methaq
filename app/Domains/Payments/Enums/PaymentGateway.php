<?php

declare(strict_types=1);

namespace App\Domains\Payments\Enums;

enum PaymentGateway: string
{
    case Stripe = 'stripe';
    case Tap = 'tap';
}
