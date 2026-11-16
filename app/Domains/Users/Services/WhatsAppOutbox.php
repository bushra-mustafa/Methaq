<?php

declare(strict_types=1);

namespace App\Domains\Users\Services;

use App\Domains\Events\Models\Event;
use App\Domains\Events\Services\InvitationAddress;
use App\Domains\Payments\Enums\PaymentRejectionReason;
use App\Domains\Payments\Models\Order;
use App\Domains\Users\Models\User;
use App\Domains\Users\Models\WhatsAppMessage;

final class WhatsAppOutbox
{
    public function record(string $key, string $phone, string $body, ?int $userId = null, int $minutes = 1440, string $kind = 'notification'): void
    {
        if (! CloudMsgSettings::get('enabled') || CloudMsg::phone($phone) === null) {
            return;
        }

        WhatsAppMessage::query()->firstOrCreate(['deduplication_key' => $key], [
            'recipient' => $phone, 'body' => $body, 'user_id' => $userId, 'kind' => $kind,
            'expires_at' => now()->addMinutes($minutes), 'next_attempt_at' => now(),
        ]);
    }

    public function receiptSubmitted(Order $order): void
    {
        $this->record('receipt-'.$order->getKey(), (string) CloudMsgSettings::get('admin_phone'),
            'وصل إثبات دفع جديد للدعوة رقم '.$order->event_id.'، باسم '.$order->user->name.'. راجعيه من لوحة الإدارة: '.url('/admin/orders'), kind: 'receipt');
    }

    public function paymentApproved(Order $order): void
    {
        $owner = $order->user;
        if (! $this->verified($owner) || $order->whatsapp_phone !== $owner->whatsapp_phone) {
            return;
        }
        $this->record('payment-approved-'.$order->getKey(), $owner->whatsapp_phone,
            'تم اعتماد الدفع للدعوة رقم '.$order->event_id.' «'.$order->event->title.'». لنشرها، ردي بكلمة نشر. إذا عندك أكثر من دعوة، اكتبي: نشر '.$order->event_id,
            (int) $owner->getKey(), kind: 'approval');
    }

    public function paymentRejected(Order $order, PaymentRejectionReason $reason): void
    {
        $owner = $order->user;
        if (! $this->verified($owner) || $order->whatsapp_phone !== $owner->whatsapp_phone) {
            return;
        }
        $this->record('payment-rejected-'.$order->getKey(), $owner->whatsapp_phone,
            'لم نعتمد قسيمة الدعوة رقم '.$order->event_id.'. '.$reason->ownerMessage()."\n".route('app.events.payment', $order->event_id),
            (int) $owner->getKey(), kind: 'rejection');
    }

    public function published(Event $event): void
    {
        $owner = $event->user;
        if ($this->verified($owner)) {
            $this->record('invitation-published-'.$event->getKey(), $owner->whatsapp_phone,
                'تم نشر دعوتك رقم '.$event->getKey().' بنجاح 🎉'."\n".InvitationAddress::url($event->subdomain)."\n".'ينتهي الرابط: '.$event->expires_at->setTimezone($event->timezone)->format('d/m/Y H:i').' ('.$event->timezone.').'."\n".'تابعي ردود الضيوف من حسابك: '.route('app.events.rsvps', $event), (int) $owner->getKey(), kind: 'publication');
        }
    }

    public function verified(User $user): bool
    {
        return $user->whatsapp_verified_at !== null && CloudMsg::phone((string) $user->whatsapp_phone) !== null;
    }
}
