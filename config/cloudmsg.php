<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('CLOUDMSG_ENABLED', false),
    'base_url' => env('CLOUDMSG_BASE_URL', 'https://cloudmsg.impaact.io/v1'),
    'api_key' => env('CLOUDMSG_API_KEY'),
    'instance_id' => env('CLOUDMSG_INSTANCE_ID'),
    'webhook_secret' => env('CLOUDMSG_WEBHOOK_SECRET'),
    'previous_webhook_secret' => env('CLOUDMSG_PREVIOUS_WEBHOOK_SECRET'),
    'previous_secret_expires_at' => env('CLOUDMSG_PREVIOUS_SECRET_EXPIRES_AT'),
    'webhook_url' => env('CLOUDMSG_WEBHOOK_URL'),
    'admin_phone' => env('CLOUDMSG_ADMIN_PHONE'),
];
