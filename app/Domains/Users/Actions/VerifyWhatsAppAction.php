<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Models\User;
use App\Domains\Users\Models\WhatsAppMessage;
use App\Domains\Users\Services\CloudMsg;
use App\Domains\Users\Services\CloudMsgSettings;
use App\Domains\Users\Services\WhatsAppOutbox;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class VerifyWhatsAppAction
{
    public function __construct(private readonly WhatsAppOutbox $outbox) {}

    public function send(User $owner, string $phone): void
    {
        $phone = CloudMsg::phone($phone);
        if ($phone === null) {
            throw ValidationException::withMessages(['phone' => 'اكتبي رقم واتساب بالصيغة الدولية، مثل +21891XXXXXXX.']);
        }
        DB::transaction(function () use ($owner, $phone): void {
            $user = User::query()->lockForUpdate()->findOrFail($owner->getKey());
            abort_unless($user->isActive() && CloudMsgSettings::get('enabled'), 403);
            if ($user->whatsapp_code_sent_at?->greaterThan(now()->subMinute())) {
                throw ValidationException::withMessages(['phone' => 'انتظري دقيقة قبل طلب رمز آخر.']);
            }
            if (User::query()->where('whatsapp_phone', $phone)->whereKeyNot($user->getKey())->exists()) {
                throw ValidationException::withMessages(['phone' => 'هذا الرقم مربوط بحساب آخر.']);
            }
            $code = (string) random_int(100000, 999999);
            $user->forceFill([
                'phone' => $phone, 'whatsapp_pending_phone' => $phone, 'whatsapp_verified_at' => null,
                'whatsapp_code_hash' => Hash::make($code), 'whatsapp_code_expires_at' => now()->addMinutes(10),
                'whatsapp_code_sent_at' => now(), 'whatsapp_code_attempts' => 0,
            ])->save();
            WhatsAppMessage::query()->where('user_id', $user->getKey())->where('deduplication_key', 'like', 'verify-'.$user->getKey().'-%')
                ->where('status', 'pending')->update(['status' => 'cancelled']);
            $this->outbox->record('verify-'.$user->getKey().'-'.Str::uuid(), $phone,
                'رمز تأكيد رقم واتساب في ميثاق: '.$code.'. صالح لمدة 10 دقائق. لا تشاركيه مع أي شخص.', (int) $user->getKey(), 10);
        }, 3);
    }

    public function confirm(User $owner, string $code): void
    {
        try {
            $valid = DB::transaction(function () use ($owner, $code): bool {
                $user = User::query()->lockForUpdate()->findOrFail($owner->getKey());
                abort_unless($user->isActive() && CloudMsgSettings::get('enabled'), 403);
                if (! $user->whatsapp_code_hash || ! $user->whatsapp_code_expires_at?->isFuture() || $user->whatsapp_code_attempts >= 5) {
                    return false;
                }
                $user->forceFill(['whatsapp_code_attempts' => $user->whatsapp_code_attempts + 1])->save();
                if (! Hash::check($code, $user->whatsapp_code_hash)) {
                    return false;
                }
                if (! $user->whatsapp_pending_phone || User::query()->where('whatsapp_phone', $user->whatsapp_pending_phone)->whereKeyNot($user->getKey())->exists()) {
                    return false;
                }
                $user->forceFill(['whatsapp_phone' => $user->whatsapp_pending_phone, 'whatsapp_pending_phone' => null, 'whatsapp_verified_at' => now(), 'whatsapp_code_hash' => null, 'whatsapp_code_expires_at' => null])->save();

                return true;
            }, 3);
        } catch (UniqueConstraintViolationException) {
            $valid = false;
        }
        if (! $valid) {
            throw ValidationException::withMessages(['code' => 'الرمز غير صحيح أو انتهت صلاحيته. بعد خمس محاولات اطلبي رمزاً جديداً.']);
        }
    }
}
