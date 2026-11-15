<?php

declare(strict_types=1);

namespace App\Domains\Users\Services;

use App\Domains\Users\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Schema;

final class WhatsAppFailures
{
    public static function count(): int
    {
        if (! Schema::hasColumn('whatsapp_messages', 'resend_of_id')) {
            return 0;
        }

        return WhatsAppMessage::query()->where('status', 'failed')->where('created_at', '>=', now()->subDay())->whereDoesntHave('resends')->count();
    }

    public static function label(?string $code): string
    {
        return match ($code) {
            null, '' => '—',
            'whatsapp_recipient_not_registered' => 'رقم المستلم غير مسجّل في واتساب؛ راجعي الرقم.',
            'http_401', 'unauthorized' => 'مفتاح API غير صحيح أو منتهي.',
            'http_403' => 'تحققي من صلاحيات المفتاح والاشتراك.',
            'http_404' => 'معرّف الرقم أو الرسالة غير موجود في الخدمة.',
            'http_409', 'whatsapp_session_not_initialized', 'whatsapp_session_start_disabled' => 'رقم الإرسال غير جاهز؛ راجعي اتصاله في CloudMsg.',
            'http_422' => 'الخدمة رفضت بيانات الرسالة؛ راجعي الرقم والإعدادات.',
            'http_429' => 'تجاوز حد الطلبات؛ انتظري المحاولة التالية.',
            'transport_error' => 'تعذّر الاتصال بخدمة واتساب.',
            'whatsapp_send_timeout' => 'انتهت مهلة التأكيد؛ قد تصل الرسالة، تأكدي قبل إعادتها.',
            'expired' => 'انتهت صلاحية الرسالة. رمز التأكيد يتطلب طلب رمز جديد.',
            'recipient_changed' => 'تغيّر رقم الحساب أو أُلغي تأكيده.',
            'provider_delivery_failed', 'whatsapp_send_failed' => 'فشل التسليم لدى الخدمة؛ راجعي تفاصيل الرسالة في CloudMsg.',
            default => 'تعذّر الإرسال ('.$code.').',
        };
    }
}
