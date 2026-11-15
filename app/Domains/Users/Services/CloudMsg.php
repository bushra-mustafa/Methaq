<?php

declare(strict_types=1);

namespace App\Domains\Users\Services;

use App\Domains\Users\Models\WhatsAppMessage;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use LogicException;

final class CloudMsg
{
    public function send(WhatsAppMessage $message): Response
    {
        $url = (string) CloudMsgSettings::get('base_url');
        if (! CloudMsgSettings::get('enabled') || ! CloudMsgSettings::get('api_key') || ! CloudMsgSettings::get('instance_id') || ! str_starts_with($url, 'https://')) {
            throw new LogicException('CloudMsg is not configured.');
        }

        return Http::acceptJson()->asJson()->withToken((string) CloudMsgSettings::get('api_key'))
            ->withHeaders(['Idempotency-Key' => $message->deduplication_key])
            ->withOptions(['allow_redirects' => false])->connectTimeout(3)->timeout(8)
            ->post(rtrim($url, '/').'/messages', [
                'instance_id' => CloudMsgSettings::get('instance_id'),
                'to' => $message->recipient,
                'type' => 'text',
                'text' => ['body' => $message->body],
            ]);
    }

    public static function phone(string $value): ?string
    {
        $value = preg_replace('/[ ()-]/', '', trim($value));

        return is_string($value) && preg_match('/^\+[1-9][0-9]{7,14}$/D', $value) === 1 ? $value : null;
    }
}
