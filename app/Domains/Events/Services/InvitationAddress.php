<?php

declare(strict_types=1);

namespace App\Domains\Events\Services;

/** The public address of an invitation, from the configured invitation domain. */
final class InvitationAddress
{
    public static function url(string $subdomain): string
    {
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME);

        return (is_string($scheme) ? $scheme : 'https').'://'.$subdomain.'.'.config('methaq.invitation_domain');
    }
}
