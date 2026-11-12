<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards every guest request on an invitation subdomain (page and RSVP) on the server:
 * unknown, unpaid or suspended invitations are 404; a published invitation whose link
 * has expired answers 410 with the «expired invitation» page and never reaches the
 * guest controllers. Expiry never touches the event's design or its template.
 */
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
            ->whereIn('status', [EventStatus::Published->value, EventStatus::Expired->value])
            ->first();

        abort_if($event === null || ! $event->is_paid || $event->suspended_at !== null, 404);

        if ($event->status === EventStatus::Expired || $event->expires_at->lessThanOrEqualTo(now())) {
            return Inertia::render('Guest/Expired', ['homeUrl' => (string) config('app.url')])->toResponse($request)->setStatusCode(Response::HTTP_GONE);
        }

        $request->attributes->set('publicEvent', $event);

        return $next($request);
    }
}
