<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolvePublishedEvent
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $subdomain = $request->route('subdomain');
        abort_unless(is_string($subdomain), 404);

        $event = Event::query()
            ->with('design')
            ->where('subdomain', $subdomain)
            ->where('status', EventStatus::Published->value)
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $request->attributes->set('publicEvent', $event);

        return $next($request);
    }
}
