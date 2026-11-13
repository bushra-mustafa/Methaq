<?php

declare(strict_types=1);

namespace App\Http\Support;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Turns Laravel's plain error responses into the branded Arabic error page. It says what happened
 * and where to go next, never why: no exception text, stack trace, route or record is shown, and
 * an unknown, unpublished or suspended invitation looks exactly like a missing page.
 */
final class BrandedErrorResponse
{
    /** @var array<int, array{title: string, message: string}> */
    private const COPY = [
        403 => ['title' => 'لا تملكين صلاحية الوصول', 'message' => 'هذه الصفحة غير متاحة لحسابك. إن كنتِ تتوقعين غير ذلك، سجّلي الدخول بالحساب الصحيح.'],
        404 => ['title' => 'لم نجد هذه الصفحة', 'message' => 'قد يكون الرابط غير صحيح، أو الصفحة لم تعد متاحة.'],
        419 => ['title' => 'انتهت صلاحية الصفحة', 'message' => 'بقيت الصفحة مفتوحة وقتاً طويلاً. حدّثيها وحاولي مرة أخرى.'],
        429 => ['title' => 'محاولات كثيرة', 'message' => 'أرسلتِ طلبات كثيرة في وقت قصير. انتظري دقيقة ثم حاولي مجدداً.'],
        500 => ['title' => 'حدث خطأ غير متوقع', 'message' => 'تعذّر إكمال طلبك، وسجّلنا المشكلة. حاولي بعد قليل.'],
        503 => ['title' => 'ميثاق تحت الصيانة', 'message' => 'نُجري تحسينات قصيرة. نعود خلال وقت قريب.'],
    ];

    public static function make(Response $response, Throwable $exception, Request $request): Response
    {
        $status = $response->getStatusCode();

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return $response;
        }

        // While debugging locally, server errors keep Laravel's own diagnostic page.
        if (config('app.debug') && $status >= 500) {
            return $response;
        }

        if (! isset(self::COPY[$status])) {
            return $response;
        }

        return Inertia::render('Error', [
            'status' => $status,
            ...self::COPY[$status],
            'homeUrl' => (string) config('app.url'),
            'meta' => PageMeta::make(self::COPY[$status]['title'], self::COPY[$status]['message'], null, null, true),
        ])->toResponse($request)->setStatusCode($status);
    }
}
