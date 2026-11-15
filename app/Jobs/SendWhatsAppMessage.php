<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domains\Users\Models\User;
use App\Domains\Users\Models\WhatsAppMessage;
use App\Domains\Users\Services\CloudMsg;
use App\Domains\Users\Services\CloudMsgSettings;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SendWhatsAppMessage implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $uniqueFor = 300;

    public int $timeout = 30;

    public function __construct(public readonly int $messageId) {}

    public function uniqueId(): string
    {
        return (string) $this->messageId;
    }

    public function handle(CloudMsg $client): void
    {
        DB::transaction(function () use ($client): void {
            $message = WhatsAppMessage::query()->lockForUpdate()->findOrFail($this->messageId);
            if ((! CloudMsgSettings::get('enabled') && $message->kind !== 'test') || $message->status !== 'pending' || $message->next_attempt_at?->isFuture()) {
                return;
            }
            if ($message->expires_at->isPast()) {
                $message->forceFill(['status' => 'failed', 'failure_code' => 'expired'])->save();

                return;
            }
            if ($message->user_id !== null) {
                $user = User::query()->find($message->user_id);
                $isVerification = str_starts_with($message->deduplication_key, 'verify-');
                $expectedPhone = $isVerification ? $user?->whatsapp_pending_phone : $user?->whatsapp_phone;
                if (! $user?->isActive() || $expectedPhone !== $message->recipient || (! $isVerification && $user->whatsapp_verified_at === null)) {
                    $message->forceFill(['status' => 'cancelled', 'failure_code' => 'recipient_changed'])->save();

                    return;
                }
            }
            $message->forceFill(['attempts' => $message->attempts + 1])->save();
            try {
                $response = $client->send($message);
                $providerId = $response->json('data.id');
                if ($response->successful() && is_string($providerId) && $providerId !== '') {
                    $message->forceFill(['status' => 'queued', 'provider_message_id' => $providerId, 'failure_code' => null])->save();

                    return;
                }
                $transient = $response->serverError() || in_array($response->status(), [408, 425, 429], true)
                    || ($response->status() === 409 && $response->json('error.code') === 'instance_not_ready');
                $delay = min(3600, max(60, (int) $response->header('Retry-After'), 30 * (2 ** min($message->attempts, 7))));
                $this->failure($message, 'http_'.$response->status(), $transient, $delay);
            } catch (Throwable) {
                // Provider exceptions may contain headers or bodies: store only a safe code.
                $this->failure($message, 'transport_error', true, 60 * min($message->attempts, 30));
            }
        }, 3);
    }

    private function failure(WhatsAppMessage $message, string $code, bool $retry, int $delay): void
    {
        $message->forceFill([
            'status' => $retry && $message->attempts < 30 ? 'pending' : 'failed',
            'failure_code' => $code, 'next_attempt_at' => now()->addSeconds($delay),
        ])->save();
    }
}
