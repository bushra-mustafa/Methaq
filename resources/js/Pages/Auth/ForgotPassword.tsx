import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Mail } from 'lucide-react';
import Honeypot from '../../Components/Honeypot';
import Turnstile from '../../Components/Turnstile';
import type { SharedPageProps } from '../../Types/PageProps';
import AuthField from '../../Components/Auth/AuthField';
import AuthLayout from '../../Components/Auth/AuthLayout';
import AuthStatus from '../../Components/Auth/AuthStatus';
import AuthSubmit from '../../Components/Auth/AuthSubmit';
import useAuthForm from '../../Components/Auth/useAuthForm';

interface ForgotPasswordProps { status?: string | null }

export default function ForgotPassword({ status }: ForgotPasswordProps) {
    const turnstile = usePage<SharedPageProps>().props.turnstile;
    const [round, setRound] = useState(0);
    const { form, submit, bind, processing } = useAuthForm('/forgot-password', { email: '', website: '', 'cf-turnstile-response': '' }, [], () => setRound((value) => value + 1));

    return <AuthLayout title="استعادة كلمة المرور" description="أدخلي بريدك وسنرسل لك رابطاً آمناً لإعادة التعيين." footer={<Link href="/login">العودة إلى تسجيل الدخول</Link>}>
        <Head title="استعادة كلمة المرور" />
        {status && <AuthStatus>إذا كان البريد مسجلاً، سيصلك رابط الاستعادة.</AuthStatus>}
        <form className="flex flex-col gap-5" onSubmit={submit} noValidate>
            <AuthField icon={Mail} label="البريد الإلكتروني" type="email" autoComplete="email" autoFocus required {...bind('email')} />
            <Honeypot {...bind('website')} />
            {turnstile && <Turnstile siteKey={turnstile.siteKey} round={round} onToken={(token) => form.setData('cf-turnstile-response', token)} error={form.errors['cf-turnstile-response']} />}
            <AuthSubmit processing={processing} pendingLabel="جاري الإرسال…">إرسال رابط الاستعادة</AuthSubmit>
        </form>
    </AuthLayout>;
}
