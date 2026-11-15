<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Models\CloudMsgWebhook;
use App\Domains\Users\Services\CloudMsgSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

final class ReceiveCloudMsgWebhookAction
{
    public function execute(Request $request): void
    {
        abort_unless(CloudMsgSettings::get('enabled'), 404);
        $raw = $request->getContent();
        abort_if(strlen($raw) > 262144, 413);
        $timestamp = $request->header('X-CloudMsg-Timestamp', '');
        $signature = $request->header('X-CloudMsg-Signature', '');
        abort_unless(ctype_digit($timestamp) && abs(now()->timestamp - (int) $timestamp) <= 300, 400);
        $secrets = [(string) CloudMsgSettings::get('webhook_secret')];
        $previousExpiry = CloudMsgSettings::get('previous_secret_expires_at');
        if ($previousExpiry && Carbon::parse($previousExpiry)->isFuture()) {
            $secrets[] = (string) CloudMsgSettings::get('previous_webhook_secret');
        }
        $valid = false;
        foreach ($secrets as $secret) {
            $valid = ($secret !== '' && hash_equals('v1='.hash_hmac('sha256', $timestamp.'.'.$raw, $secret), $signature)) || $valid;
        }
        abort_unless($valid, 401);
        $payload = json_decode($raw, true);
        abort_unless(is_array($payload), 400);
        Validator::make($payload, [
            'id' => ['required', 'string', 'max:128'], 'type' => ['required', 'string', 'max:80'],
            'data' => ['required', 'array'], 'data.instance_id' => ['required', 'string', 'max:128'],
        ])->validate();
        abort_unless(hash_equals($payload['id'], (string) $request->header('X-CloudMsg-Event-Id')), 400);
        abort_unless(hash_equals((string) CloudMsgSettings::get('instance_id'), $payload['data']['instance_id']), 403);
        CloudMsgWebhook::query()->firstOrCreate(['provider_event_id' => $payload['id']], ['payload' => $payload]);
    }
}
