<?php

declare(strict_types=1);

use App\Domains\Editor\Exceptions\DesignRevisionConflict;
use App\Domains\Editor\Exceptions\InvalidDesignAssetReference;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequireAdminWithTwoFactor;
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
        $designPayload = static fn (Request $request): bool => $request->isMethod('PATCH') && $request->is('app/events/*/design');
        $middleware->convertEmptyStringsToNull(except: [$designPayload]);
        $middleware->trimStrings(except: [$designPayload]);
        $middleware->web(append: [HandleInertiaRequests::class]);
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'admin.2fa' => RequireAdminWithTwoFactor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
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
