<?php

declare(strict_types=1);

use App\Domains\Editor\Exceptions\DesignRevisionConflict;
use App\Domains\Editor\Exceptions\InvalidDesignAssetReference;
use App\Domains\Editor\Exceptions\ProposalRevisionConflict;
use App\Http\Middleware\EnforceHttps;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\GuardPublicForms;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequireAdminWithTwoFactor;
use App\Http\Middleware\RestrictPrototypeRoutes;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SendAdministratorsToTwoFactorSetup;
use App\Http\Support\BrandedErrorResponse;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $designPayload = static fn (Request $request): bool => $request->isMethod('PATCH') && ($request->is('app/events/*/design') || $request->is('app/proposals/ivory-wedding'));
        $middleware->convertEmptyStringsToNull(except: [$designPayload]);
        $middleware->trimStrings(except: [$designPayload]);
        $middleware->prepend(RestrictPrototypeRoutes::class);
        // After TrustProxies, so the forwarded scheme is known before deciding whether to redirect.
        $middleware->append(EnforceHttps::class);
        $middleware->append(SecurityHeaders::class);
        // Only the app domain and its invitation subdomains may be served; a forged Host header (reset-link poisoning) gets a 400.
        $middleware->trustHosts(
            at: static fn (): array => array_values(array_filter([
                parse_url((string) config('app.url'), PHP_URL_HOST) ?: null,
                config('methaq.invitation_domain'),
                ...array_map('trim', explode(',', (string) env('METHAQ_TRUSTED_HOSTS', ''))),
            ])),
            subdomains: true,
        );
        $middleware->web(append: [GuardPublicForms::class, HandleInertiaRequests::class]);
        // A changed password ends every session that still holds the old one.
        $middleware->authenticateSessions();
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'admin.2fa' => RequireAdminWithTwoFactor::class,
        ]);
        // Laravel moves authentication ahead of plain middleware; keep the /admin
        // two-factor setup redirect before Filament's Authenticate (403).
        $middleware->prependToPriorityList(AuthenticatesRequests::class, SendAdministratorsToTwoFactorSetup::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(BrandedErrorResponse::make(...));
        $exceptions->render(function (ProposalRevisionConflict $exception, Request $request): ?JsonResponse {
            return $request->expectsJson() ? response()->json(['message' => $exception->getMessage(), 'snapshot' => $exception->snapshot], 409) : null;
        });
        $exceptions->render(function (DesignRevisionConflict $exception, Request $request): ?JsonResponse {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => 'حُفظ تعديل أحدث من جلسة أخرى.',
                'snapshot' => $exception->snapshot->toArray(),
            ], 409);
        });
        $exceptions->render(function (InvalidDesignAssetReference $exception, Request $request): ?JsonResponse {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => 'تعذّر حفظ التصميم.',
                'errors' => ['document' => [$exception->getMessage()]],
            ], 422);
        });
    })->create();
