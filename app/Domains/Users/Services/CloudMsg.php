<?php

declare(strict_types=1);

namespace App\Domains\Users\Services;

use App\Domains\Users\Models\WhatsAppMessage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use LogicException;

final class CloudMsg
{
    private function request(): PendingRequest
    {
        if (! CloudMsgSettings::get('api_key') || ! CloudMsgSettings::get('instance_id') || ! str_starts_with((string) CloudMsgSettings::get('base_url'), 'https://')) {
            throw new LogicException('CloudMsg is not configured.');
        }

        return Http::acceptJson()->asJson()->withToken((string) CloudMsgSettings::get('api_key'))
            ->withOptions(['allow_redirects' => false])->connectTimeout(3)->timeout(8);
    }

    public function status(): Response
    {
        return $this->request()->get(rtrim((string) CloudMsgSettings::get('base_url'), '/').'/instances/'.rawurlencode((string) CloudMsgSettings::get('instance_id')).'/status');
    }

    public function messageStatus(string $id): Response
    {
        return $this->request()->get(rtrim((string) CloudMsgSettings::get('base_url'), '/').'/messages/'.rawurlencode($id));
    }

    public function send(WhatsAppMessage $message): Response
    {
        if (! CloudMsgSettings::get('enabled') && $message->kind !== 'test') {
            throw new LogicException('CloudMsg is disabled.');
        }
        $base = rtrim((string) CloudMsgSettings::get('base_url'), '/');
        $request = $this->request()->withHeaders(['Idempotency-Key' => $message->deduplication_key]);
        $source = $message->source;
        if ($source?->provider_message_id && $source->recipient === $message->recipient) {
            return $request->post($base.'/messages/'.rawurlencode($source->provider_message_id).'/resend', []);
        }

        return $request->post($base.'/messages', [
            'instance_id' => CloudMsgSettings::get('instance_id'), 'to' => $message->recipient,
            'type' => 'text', 'text' => ['body' => $message->body],
        ]);
    }

    public static function phone(string $value): ?string
    {
        $value = preg_replace('/[ ()-]/', '', trim($value));

        return is_string($value) && preg_match('/^\+[1-9][0-9]{7,14}$/D', $value) === 1 ? $value : null;
    }
}
