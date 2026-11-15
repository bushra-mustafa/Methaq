<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Events\Actions\PublishEventAction;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Events\Services\InvitationAddress;
use App\Domains\Users\Models\CloudMsgWebhook;
use App\Domains\Users\Models\User;
use App\Domains\Users\Models\WhatsAppMessage;
use App\Domains\Users\Services\CloudMsg;
use App\Domains\Users\Services\CloudMsgSettings;
use App\Domains\Users\Services\WhatsAppOutbox;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ProcessCloudMsgWebhookAction
{
    public function __construct(private readonly PublishEventAction $publish, private readonly WhatsAppOutbox $outbox) {}

    public function execute(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $event = CloudMsgWebhook::query()->lockForUpdate()->findOrFail($id);
            if ($event->status !== 'pending' || $event->next_attempt_at?->isFuture() || ! CloudMsgSettings::get('enabled')) {
                return;
            }
            $payload = $event->payload;
            if (($payload['data']['instance_id'] ?? null) !== CloudMsgSettings::get('instance_id')) {
                $event->forceFill(['status' => 'ignored', 'processed_at' => now()])->save();

                return;
            }
            if (($payload['type'] ?? null) === 'message.received') {
                $this->incoming($event, $payload['data']);
            } else {
                if (! $this->delivery($payload['type'] ?? '', $payload['data']) && $event->attempts < 10) {
                    // A delivery callback can race the commit that records its provider ID.
                    $event->forceFill(['attempts' => $event->attempts + 1, 'next_attempt_at' => now()->addMinute()])->save();

                    return;
                }
            }
            $event->forceFill(['status' => 'processed', 'processed_at' => now()])->save();
        }, 3);
    }

    /** @param array<string, mixed> $message */
    private function incoming(CloudMsgWebhook $webhook, array $message): void
    {
        $from = $message['from'] ?? '';
        $chat = $message['chat_id'] ?? '';
        $text = $message['text']['body'] ?? null;
        if (($message['type'] ?? null) !== 'text' || ! is_string($text) || ! is_string($from) || $chat !== $from
            || preg_match('/^([1-9][0-9]{7,14})@c\.us$/D', $from, $match) !== 1) {
            return;
        }
        $text = strtr(trim($text), ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
        if (preg_match('/^نشر(?:\s+([1-9][0-9]{0,9}))?$/uD', $text, $command) !== 1) {
            return;
        }
        $phone = CloudMsg::phone('+'.$match[1]);
        $owner = User::query()->where('whatsapp_phone', $phone)->whereNotNull('whatsapp_verified_at')->first();
        if (! $owner instanceof User || ! $owner->isActive()) {
            return;
        }
        // Only this owner's records are ever considered, even for an explicitly supplied ID.
        $query = Event::query()->where('user_id', $owner->getKey())->whereNull('suspended_at')->where('expires_at', '>', now());
        if (isset($command[1])) {
            $events = $query->whereKey((int) $command[1])->get();
        } else {
            $events = $query->where('is_paid', true)->where('event_date', '>', now())
                ->where('status', EventStatus::Draft->value)->limit(11)->get();
            if ($events->isEmpty()) {
                $events = Event::query()->where('user_id', $owner->getKey())->where('status', EventStatus::Published->value)
                    ->whereNull('suspended_at')->where('expires_at', '>', now())->limit(11)->get();
            }
        }
        if ($events->count() !== 1) {
            $body = $events->isEmpty() ? 'لا توجد دعوة متاحة للنشر. تأكدي من اعتماد الدفع وتجهيز الدعوة في الموقع.'
                : 'عندك أكثر من دعوة. حددي المطلوبة بكتابة «نشر رقم الدعوة»: '.implode('، ', $events->take(10)->map(fn (Event $event): string => $event->getKey().' — '.$event->title)->all());
            $this->reply($webhook, $owner, $body);

            return;
        }
        $event = $events->first();
        // Lock before deciding whether this is a new publish or a request for an existing link.
        $event = Event::query()->lockForUpdate()->findOrFail($event->getKey());
        if ($event->status === EventStatus::Published && $event->suspended_at === null && $event->expires_at->isFuture()) {
            $this->reply($webhook, $owner, 'دعوتك منشورة بالفعل: '.InvitationAddress::url($event->subdomain));

            return;
        }
        try {
            $this->publish->execute($owner, $event, null, (int) $event->design?->revision);
        } catch (ValidationException $exception) {
            $this->reply($webhook, $owner, (string) collect($exception->errors())->flatten()->first().' '.route('app.events.payment', $event));
        }
    }

    private function reply(CloudMsgWebhook $event, User $owner, string $body): void
    {
        $this->outbox->record('reply-'.hash('sha256', $event->provider_event_id), $owner->whatsapp_phone, $body, (int) $owner->getKey());
    }

    /** @param array<string, mixed> $data */
    private function delivery(string $type, array $data): bool
    {
        $status = match ($type) {
            'message.sent' => 'sent', 'message.ack' => 'acknowledged',
            'message.failed', 'message.invalid', 'message.expired', 'message.cancelled' => 'failed',
            default => null,
        };
        if ($status === null || ! is_string($data['message_id'] ?? null)) {
            return true;
        }
        $message = WhatsAppMessage::query()->where('provider_message_id', $data['message_id'])->lockForUpdate()->first();
        if ($message === null) {
            return false;
        }
        if ($message->status === 'acknowledged' || ($status === 'sent' && $message->status === 'failed')) {
            return true;
        }
        $code = $data['failure_code'] ?? null;
        $failure = is_string($code) && preg_match('/^[a-z0-9_]{1,80}$/D', $code) === 1 ? $code : 'provider_delivery_failed';
        $message->forceFill(['status' => $status, 'failure_code' => $status === 'failed' ? $failure : null])->save();

        return true;
    }
}
