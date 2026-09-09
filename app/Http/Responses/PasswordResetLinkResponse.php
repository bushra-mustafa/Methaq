<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Symfony\Component\HttpFoundation\Response;

final class PasswordResetLinkResponse implements FailedPasswordResetLinkRequestResponse, SuccessfulPasswordResetLinkRequestResponse
{
    private const MESSAGE = 'إذا كان البريد مسجلاً، سيصلك رابط الاستعادة.';

    public function __construct(string $status) {}

    public function toResponse($request): Response
    {
        if (! $request instanceof Request) {
            return new JsonResponse(['message' => self::MESSAGE]);
        }

        return $request->wantsJson()
            ? new JsonResponse(['message' => self::MESSAGE])
            : back()->withInput($request->only('email'))->with('status', self::MESSAGE);
    }
}
