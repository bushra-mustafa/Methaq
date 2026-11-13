import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AuthField from '../../Components/Auth/AuthField';
import AuthLayout from '../../Components/Auth/AuthLayout';
import AuthSubmit from '../../Components/Auth/AuthSubmit';
import useAuthForm from '../../Components/Auth/useAuthForm';

export default function TwoFactorChallenge() {
    const [recoveryMode, setRecoveryMode] = useState(false);
    const { form, submit, bind, processing } = useAuthForm('/two-factor-challenge', { code: '', recovery_code: '' }, ['code', 'recovery_code']);
    const code = bind('code');

    return <AuthLayout title="التحقق بخطوتين" description={recoveryMode ? 'أدخلي أحد رموز الاسترداد المحفوظة.' : 'أدخلي الرمز المكون من 6 أرقام من تطبيق المصادقة.'}>
        <Head title="التحقق بخطوتين" />
        <form className="flex flex-col gap-5" onSubmit={submit} noValidate>
            {recoveryMode
                ? <AuthField key="recovery" label="رمز الاسترداد" autoComplete="one-time-code" autoFocus required dir="ltr" {...bind('recovery_code')} />
                : <AuthField key="code" label="رمز التحقق" inputMode="numeric" autoComplete="one-time-code" autoFocus required dir="ltr" className="text-center text-[22px] tracking-[0.5em]" {...code} onChange={(event) => form.setData('code', event.target.value.replace(/\D/g, '').slice(0, 6))} />}
            <AuthSubmit processing={processing}>تحقق</AuthSubmit>
            <button type="button" onClick={() => setRecoveryMode(!recoveryMode)} className="inline-flex min-h-11 items-center justify-center text-[15px] font-semibold text-methaq-primary underline decoration-methaq-gold/60 underline-offset-8 hover:decoration-methaq-gold">{recoveryMode ? 'استخدام رمز تطبيق المصادقة' : 'استخدام رمز استرداد'}</button>
        </form>
    </AuthLayout>;
}
