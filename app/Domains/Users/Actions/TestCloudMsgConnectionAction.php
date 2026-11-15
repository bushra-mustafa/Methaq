<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Models\WhatsAppMessage;
use App\Domains\Users\Services\AuditLogger;
use App\Domains\Users\Services\CloudMsg;
use App\Domains\Users\Services\CloudMsgSettings;
use App\Jobs\SendWhatsAppMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

final class TestCloudMsgConnectionAction
{
    public function __construct(private readonly CloudMsg $client, private readonly AuditLogger $audit) {}

    public function execute(User $actor, string $confirmedRecipient): WhatsAppMessage
    {
        Gate::forUser($actor)->authorize('manage-whatsapp-settings');
        $recipient = (string) CloudMsgSettings::get('admin_phone');
        if (CloudMsg::phone($recipient) === null || $recipient !== $confirmedRecipient || ! CloudMsgSettings::get('api_key') || ! CloudMsgSettings::get('instance_id')) {
            throw ValidationException::withMessages(['recipient' => 'احفظي مفتاح API ومعرّف الرقم ورقم استقبال صحيح قبل الاختبار.']);
        }
        try {
            $response = $this->client->status();
        } catch (Throwable) {
            throw ValidationException::withMessages(['recipient' => 'تعذّر الاتصال بالخدمة. راجعي الإعدادات والاتصال من الاستضافة.']);
        }
        if (! $response->successful() || $response->json('data.status') !== 'ready') {
            throw ValidationException::withMessages(['recipient' => $response->status() === 401 ? 'مفتاح API غير صحيح أو منتهي.' : 'رقم الإرسال غير جاهز. اربطيه في CloudMsg وتحققي من صلاحيات المفتاح والاشتراك.']);
        }
        $message = WhatsAppMessage::query()->firstOrCreate([
            'deduplication_key' => 'test-admin-'.$actor->getKey().'-'.intdiv(now()->timestamp, 60).'-'.substr(hash('sha256', $recipient.'|'.CloudMsgSettings::get('instance_id')), 0, 16),
        ], [
            'kind' => 'test', 'recipient' => $recipient, 'body' => 'رسالة اختبار من ميثاق ✅ إذا وصلتك هذه الرسالة، إرسال واتساب يعمل.',
            'expires_at' => now()->addMinutes(5), 'next_attempt_at' => now(),
        ]);
        // Inline processing makes a stopped queue visible rather than hiding the test behind it.
        (new SendWhatsAppMessage((int) $message->getKey()))->handle($this->client);
        $this->audit->record($actor, AuditAction::WhatsAppConnectionTested, AuditSubjectType::Setting, 0, metadata: ['messageId' => $message->getKey()]);

        return $message->refresh();
    }
}
