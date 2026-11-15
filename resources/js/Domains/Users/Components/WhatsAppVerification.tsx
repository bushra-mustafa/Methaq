import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

export interface WhatsAppState {
    enabled: boolean;
    phone: string;
    verified: boolean;
    prepared: boolean;
}

export function WhatsAppVerification({ state }: { state: WhatsAppState }) {
    const phone = useForm({ phone: state.phone });
    const code = useForm({ code: '' });
    const matchesVerified = state.verified && phone.data.phone === state.phone;
    function send(event: FormEvent): void {
        event.preventDefault();
        if (!/^\+[1-9][0-9]{7,14}$/.test(phone.data.phone)) {
            phone.setError('phone', 'اكتبي الرقم بالصيغة الدولية، مثل +21891XXXXXXX.');
            return;
        }
        phone.post('/app/whatsapp/send-code', { preserveScroll: true });
    }
    function confirm(event: FormEvent): void {
        event.preventDefault();
        if (!/^[0-9]{6}$/.test(code.data.code)) {
            code.setError('code', 'اكتبي الرمز المكوّن من ستة أرقام.');
            return;
        }
        code.post('/app/whatsapp/confirm', { preserveScroll: true, onSuccess: () => code.reset() });
    }
    return <section className="payment-whatsapp" aria-labelledby="whatsapp-title">
        <h2 id="whatsapp-title">رقم واتساب للدعوة</h2>
        <p>بعد اعتماد الدفع، تصلك رسالة على هذا الرقم. ردي بكلمة «نشر» لنشر دعوتك واستلام رابطها.</p>
        <form className="payment-form" onSubmit={send}>
            <label className="account-field" htmlFor="whatsapp-phone"><span>رقم واتساب بالصيغة الدولية</span><input id="whatsapp-phone" type="tel" readOnly={state.verified} dir="ltr" autoComplete="tel" required maxLength={16} value={phone.data.phone} onChange={(event) => phone.setData('phone', event.target.value)} />{phone.errors.phone && <small role="alert">{phone.errors.phone}</small>}</label>
            <button className="account-secondary" type="submit" disabled={phone.processing || matchesVerified}>{phone.processing ? 'جاري طلب الرمز…' : matchesVerified ? 'الرقم مؤكد ✓' : 'إرسال رمز على واتساب'}</button>
        </form>
        {state.verified && <p>لتغيير الرقم، حدّثيه في <Link href="/app/profile">ملفك الشخصي</Link> ثم أكديه هنا.</p>}
        {!state.verified && <form className="payment-form" onSubmit={confirm}>
            <label className="account-field" htmlFor="whatsapp-code"><span>رمز التأكيد</span><input id="whatsapp-code" inputMode="numeric" autoComplete="one-time-code" dir="ltr" required maxLength={6} value={code.data.code} onChange={(event) => code.setData('code', event.target.value)} />{code.errors.code && <small role="alert">{code.errors.code}</small>}</label>
            <button className="account-secondary" type="submit" disabled={code.processing}>{code.processing ? 'جاري التأكيد…' : 'تأكيد الرقم'}</button>
            <p>الرمز صالح لعشر دقائق. تقدري تطلبي رمزاً جديداً بعد دقيقة؛ وصول الرسالة قد يستغرق دقيقة.</p>
        </form>}
    </section>;
}
