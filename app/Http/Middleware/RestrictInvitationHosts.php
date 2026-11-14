<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invitation addresses (`name.methq.ly`) exist only to show a guest's invitation and take the reply.
 * Sign-in, the account area and the admin panel answer on the main address alone, so a customer cannot
 * dress up an invitation address as a Methaq login page. `www` is sent to the main address.
 */
final class RestrictInvitationHosts
{
    /** Paths a guest address may serve. */
    private const GUEST_PATHS = ['/', 'rsvp', 'robots.txt', 'up'];

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $domain = strtolower((string) config('methaq.invitation_domain'));
        $mainHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        if ($domain === '' || $host === $mainHost || $host === $domain || ! str_ends_with($host, '.'.$domain)) {
            return $next($request);
        }

        if ($host === 'www.'.$domain || $host === 'www.'.$mainHost) {
            abort_unless($request->isMethodSafe(), 404);

            return redirect()->to(rtrim((string) config('app.url'), '/').$request->getRequestUri(), 301);
        }

        abort_unless(in_array($request->path(), self::GUEST_PATHS, true), 404);

        return $next($request);
    }
}
