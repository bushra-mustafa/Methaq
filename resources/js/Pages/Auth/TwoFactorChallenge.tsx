import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { AuthShell } from '../../Domains/Users/Components/AuthShell';
import { FormField } from '../../Domains/Users/Components/FormField';

export default function TwoFactorChallenge() {
    const [recoveryMode, setRecoveryMode] = useState(false);
    const form = useForm({ code: '', recovery_code: '' });
    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        form.post('/two-factor-challenge', { onFinish: () => form.reset('code', 'recovery_code') });
    }

    return <AuthShell title="التحقق بخطوتين" description={recoveryMode ? 'أدخلي أحد رموز الاسترداد المحفوظة.' : 'أدخلي الرمز المكون من 6 أرقام من تطبيق المصادقة.'}>
        <Head title="التحقق بخطوتين" />
        <form className="account-form" onSubmit={submit} noValidate>
            {recoveryMode
                ? <FormField label="رمز الاسترداد" name="recovery_code" value={form.data.recovery_code} onChange={(event) => form.setData('recovery_code', event.target.value)} autoComplete="one-time-code" autoFocus required error={form.errors.recovery_code} />
                : <FormField label="رمز التحقق" name="code" inputMode="numeric" value={form.data.code} onChange={(event) => form.setData('code', event.target.value.replace(/\D/g, '').slice(0, 6))} autoComplete="one-time-code" autoFocus required error={form.errors.code} />}
            <button className="account-primary" type="submit" disabled={form.processing}>{form.processing ? 'جاري التحقق…' : 'تحقق ودخول'}</button>
            <button className="account-text-button" type="button" onClick={() => setRecoveryMode(!recoveryMode)}>{recoveryMode ? 'استخدام رمز تطبيق المصادقة' : 'استخدام رمز استرداد'}</button>
        </form>
    </AuthShell>;
}
