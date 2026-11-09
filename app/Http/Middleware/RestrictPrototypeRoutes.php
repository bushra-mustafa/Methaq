<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RestrictPrototypeRoutes
{
    private const PROTOTYPE_PATHS = [
        'dev/design-lab',
        'app/proposals/ivory-wedding',
    ];

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is(self::PROTOTYPE_PATHS)) {
            abort_unless(app()->environment(['local', 'testing']), 404);
        }

        return $next($request);
    }
}
