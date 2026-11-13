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
     * Redirect plain HTTP to HTTPS, generate https URLs and send HSTS. On by default in
     * production; local development stays on http. The reverse proxy must forward the scheme
     * (see TRUSTED_PROXIES in docs/deployment.md).
     */
    /*
     * Optional visit statistics on the public pages only (Plausible, no cookies, no personal data).
     * Off unless ANALYTICS_ENABLED is true in production AND PLAUSIBLE_DOMAIN is set; it also waits for
     * each visitor's consent. Nothing here is ever sent from account, editor, payment or invitation pages.
     */
    'analytics' => [
        'enabled' => (bool) env('ANALYTICS_ENABLED', false) && env('APP_ENV') === 'production',
        'domain' => env('PLAUSIBLE_DOMAIN'),
        'script' => env('PLAUSIBLE_SCRIPT_URL', 'https://plausible.io/js/script.exclusions.js'),
        'exclude' => '/app/*,/admin/*,/login,/register,/forgot-password,/reset-password/*,/two-factor-challenge,/user/*,/email/*,/start/*',
    ],

    /* Addresses of the reverse proxy / Cloudflare whose forwarded scheme and host are trusted: comma list or *. */
    'trusted_proxies' => env('TRUSTED_PROXIES'),

    'force_https' => (bool) env('METHAQ_FORCE_HTTPS', env('APP_ENV') === 'production'),

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
