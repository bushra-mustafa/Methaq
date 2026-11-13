<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * In production every page is served over https: plain http is redirected (permanently) and
 * secure responses carry HSTS. The health check stays reachable for the host's monitors.
 * Local development and tests keep http because `methaq.force_https` is off there.
 */
final class EnforceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('methaq.force_https') || $request->is('up')) {
            return $next($request);
        }

        if (! $request->isSecure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        return $response;
    }
}
