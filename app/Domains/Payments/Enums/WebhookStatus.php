<?php

declare(strict_types=1);

namespace App\Domains\Payments\Enums;

enum WebhookStatus: string
{
    case Received = 'received';
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';
}
