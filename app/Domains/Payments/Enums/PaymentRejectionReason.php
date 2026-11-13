<?php

declare(strict_types=1);

namespace App\Domains\Payments\Enums;

use Filament\Support\Contracts\HasLabel;

/** Why a receipt was rejected; the value is stored in orders.failure_code and shown to the owner. */
enum PaymentRejectionReason: string implements HasLabel
{
    case NotReceived = 'not_received';
    case UnclearReceipt = 'unclear_receipt';
    case AmountMismatch = 'amount_mismatch';

    public function getLabel(): string
    {
        return match ($this) {
            self::NotReceived => 'المبلغ لم يصل إلى الحساب',
            self::UnclearReceipt => 'القسيمة غير واضحة',
            self::AmountMismatch => 'المبلغ لا يطابق قيمة الطلب',
        };
    }

    /** Message for the event owner on the payment page. */
    public function ownerMessage(): string
    {
        return match ($this) {
            self::NotReceived => 'لم يصلنا المبلغ في الحساب. تأكدي من إتمام التحويل ثم ارفعي القسيمة مرة أخرى.',
            self::UnclearReceipt => 'القسيمة غير واضحة. ارفعي صورة أوضح تظهر فيها بيانات التحويل كاملة.',
            self::AmountMismatch => 'المبلغ المحوَّل لا يطابق قيمة الطلب. حوّلي المبلغ الصحيح وارفعي قسيمة جديدة.',
        };
    }
}
