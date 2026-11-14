<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Events\Services\VisitCounter;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Counts successful opens of the public pages (not the account area, the admin panel or invitations). */
final class CountSiteVisit
{
    private const PAGES = ['home', 'templates.index', 'occasions.show', 'about', 'contact', 'privacy', 'terms'];

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if ($response->getStatusCode() === Response::HTTP_OK
            && in_array($request->route()?->getName(), self::PAGES, true)
            && ! ($user instanceof User && $user->role === UserRole::Admin)) {
            app(VisitCounter::class)->recordSiteView($request);
        }

        return $response;
    }
}
