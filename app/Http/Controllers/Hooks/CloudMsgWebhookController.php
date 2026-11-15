<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hooks;

use App\Domains\Users\Actions\ReceiveCloudMsgWebhookAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class CloudMsgWebhookController extends Controller
{
    public function __invoke(Request $request, ReceiveCloudMsgWebhookAction $receive): Response
    {
        $receive->execute($request);

        return response('', 204);
    }
}
