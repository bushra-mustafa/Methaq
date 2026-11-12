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
];
