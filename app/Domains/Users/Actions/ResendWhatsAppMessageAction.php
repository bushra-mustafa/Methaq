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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ResendWhatsAppMessageAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function recipient(WhatsAppMessage $message): string
    {
        return $message->user_id === null ? (string) CloudMsgSettings::get('admin_phone') : $message->recipient;
    }

    public function execute(User $actor, int $messageId, string $confirmedRecipient): WhatsAppMessage
    {
        Gate::forUser($actor)->authorize('manage-whatsapp-settings');

        return DB::transaction(function () use ($actor, $messageId, $confirmedRecipient): WhatsAppMessage {
            $source = WhatsAppMessage::query()->lockForUpdate()->findOrFail($messageId);
            $existing = $source->resends()->first();
            if ($existing) {
                return $existing;
            }
            if (! CloudMsgSettings::get('enabled') || $source->status !== 'failed' || $source->kind === 'verification' || str_starts_with($source->deduplication_key, 'verify-')) {
                throw ValidationException::withMessages(['recipient' => 'يمكن إعادة الرسائل الفاشلة فقط. رمز التأكيد يحتاج طلب رمز جديد من صفحة الدفع.']);
            }
            if ($source->expires_at->isPast()) {
                throw ValidationException::withMessages(['recipient' => 'انتهت صلاحية هذه الرسالة؛ لا يمكن إعادة محتوى قديم.']);
            }
            $recipient = $this->recipient($source);
            if (CloudMsg::phone($recipient) === null || $confirmedRecipient !== $recipient) {
                throw ValidationException::withMessages(['recipient' => 'تغيّر رقم الاستقبال. افتحي التأكيد من جديد وراجعيه في الإعدادات.']);
            }
            if ($source->user_id !== null) {
                $owner = User::query()->find($source->user_id);
                if (! $owner?->isActive() || $owner->whatsapp_verified_at === null || $owner->whatsapp_phone !== $recipient) {
                    throw ValidationException::withMessages(['recipient' => 'الرقم لم يعد الرقم المؤكد لهذا الحساب.']);
                }
            }
            $copy = WhatsAppMessage::query()->create([
                'deduplication_key' => 'retry-'.$source->getKey(), 'resend_of_id' => $source->getKey(),
                'kind' => $source->kind, 'recipient' => $recipient, 'body' => $source->body, 'user_id' => $source->user_id,
                'expires_at' => $source->expires_at, 'next_attempt_at' => now(),
            ]);
            $this->audit->record($actor, AuditAction::WhatsAppMessageRetried, AuditSubjectType::Setting, 0,
                metadata: ['messageId' => $source->getKey(), 'retryId' => $copy->getKey(), 'recipientChanged' => $source->recipient !== $recipient]);

            return $copy;
        }, 3);
    }
}
