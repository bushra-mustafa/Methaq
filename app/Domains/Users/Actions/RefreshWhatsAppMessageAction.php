<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Models\User;
use App\Domains\Users\Models\WhatsAppMessage;
use App\Domains\Users\Services\CloudMsg;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

final class RefreshWhatsAppMessageAction
{
    public function __construct(private readonly CloudMsg $client) {}

    public function execute(User $actor, int $messageId): WhatsAppMessage
    {
        Gate::forUser($actor)->authorize('manage-whatsapp-settings');
        $message = WhatsAppMessage::query()->findOrFail($messageId);
        if (! $message->provider_message_id) {
            throw ValidationException::withMessages(['recipient' => 'هذه الرسالة لم تُقبل لدى الخدمة بعد.']);
        }
        try {
            $response = $this->client->messageStatus($message->provider_message_id);
        } catch (Throwable) {
            throw ValidationException::withMessages(['recipient' => 'تعذّر قراءة الحالة من الخدمة. حاولي لاحقاً.']);
        }
        if (! $response->successful()) {
            throw ValidationException::withMessages(['recipient' => 'لم نتمكن من قراءة الحالة. تحققي من المفتاح وصلاحية messages:read.']);
        }
        $status = match ($response->json('data.status')) {
            'queued', 'sending' => 'queued', 'sent' => 'sent', 'delivered', 'read' => 'acknowledged',
            'failed', 'invalid', 'expired', 'cancelled' => 'failed', default => null,
        };
        if ($status === null) {
            throw ValidationException::withMessages(['recipient' => 'الخدمة رجعت حالة غير معروفة؛ لم نغيّر الرسالة.']);
        }
        $code = $response->json('data.failure_code');
        $code = is_string($code) && preg_match('/^[a-z0-9_]{1,80}$/D', $code) ? $code : 'provider_delivery_failed';

        return DB::transaction(function () use ($messageId, $status, $code): WhatsAppMessage {
            $locked = WhatsAppMessage::query()->lockForUpdate()->findOrFail($messageId);
            if ($locked->status !== 'acknowledged') {
                $locked->forceFill(['status' => $status, 'failure_code' => $status === 'failed' ? $code : null])->save();
            }

            return $locked;
        }, 3);
    }
}
