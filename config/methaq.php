<?php

declare(strict_types=1);

return [
    /* The parent domain used for guest invitations, e.g. sana-ali.methaq.link. */
    'invitation_domain' => env('METHAQ_INVITATION_DOMAIN', 'methaq.test'),

    /*
     * A guest link stays open through the event day and this many following days,
     * counted in the invitation timezone, then closes at midnight (0–7, default 2).
     */
    'invitation_grace_days' => (int) env('METHAQ_INVITATION_GRACE_DAYS', 2),

    'invitation_timezone' => env('METHAQ_INVITATION_TIMEZONE', 'Africa/Tripoli'),

    /*
     * Manual bank transfer (first release). Prices are stored in LYD minor units
     * (1 LYD = 1000 dirhams). The receiving account is shown to the customer on the
     * payment page and lives in .env only; uploads stay closed until it is set.
     */
    'payment' => [
        'currency' => 'LYD',
        'bank_transfer' => [
            'bank_name' => env('METHAQ_BANK_NAME'),
            'account_name' => env('METHAQ_BANK_ACCOUNT_NAME'),
            'account_number' => env('METHAQ_BANK_ACCOUNT_NUMBER'),
            'iban' => env('METHAQ_BANK_IBAN'),
        ],
    ],
];
