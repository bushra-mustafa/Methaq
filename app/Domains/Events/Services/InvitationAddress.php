<?php

declare(strict_types=1);

namespace App\Domains\Events\Services;

/** The public address of an invitation, from the configured invitation domain. */
final class InvitationAddress
{
    public static function url(string $subdomain): string
    {
        $configured = parse_url((string) config('app.url'), PHP_URL_SCHEME);
        $scheme = config('methaq.force_https') ? 'https' : (is_string($configured) ? $configured : 'https');

        return $scheme.'://'.$subdomain.'.'.config('methaq.invitation_domain');
    }
}
