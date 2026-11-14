import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Mail, Phone, User } from 'lucide-react';
import Honeypot from '../../Components/Honeypot';
import Turnstile from '../../Components/Turnstile';
import type { SharedPageProps } from '../../Types/PageProps';
import AuthField from '../../Components/Auth/AuthField';
import AuthLayout from '../../Components/Auth/AuthLayout';
import AuthSubmit from '../../Components/Auth/AuthSubmit';
import PasswordField from '../../Components/Auth/PasswordField';
import useAuthForm from '../../Components/Auth/useAuthForm';

export default function Register() {
    const turnstile = usePage<SharedPageProps>().props.turnstile;
    const [round, setRound] = useState(0);
    const { form, submit, bind, processing } = useAuthForm('/register', { name: '', email: '', phone: '', password: '', password_confirmation: '', website: '', 'cf-turnstile-response': '' }, ['password', 'password_confirmation'], () => setRound((value) => value + 1));

    return <AuthLayout title="ابدئي دعوتك" description="حساب واحد يجمع تصاميمك وروابط مناسباتك، وتجرّبين قبل أن تدفعي." footer={<p className="m-0">عندك حساب؟ <Link href="/login">سجلي الدخول</Link></p>}>
        <Head title="إنشاء حساب" />
        <form className="flex flex-col gap-5" onSubmit={submit} noValidate>
            <AuthField icon={User} label="الاسم" autoComplete="name" autoFocus required {...bind('name')} />
            <AuthField icon={Mail} label="البريد الإلكتروني" type="email" autoComplete="email" required {...bind('email')} />
            <AuthField icon={Phone} label="رقم الهاتف (اختياري)" type="tel" autoComplete="tel" {...bind('phone')} />
            <PasswordField label="كلمة المرور" autoComplete="new-password" required hint="12 حرفاً على الأقل، مع حرف كبير ورقم ورمز." {...bind('password')} />
            <PasswordField label="تأكيد كلمة المرور" autoComplete="new-password" required {...bind('password_confirmation')} />
            <Honeypot {...bind('website')} />
            {turnstile && <Turnstile siteKey={turnstile.siteKey} round={round} onToken={(token) => form.setData('cf-turnstile-response', token)} error={form.errors['cf-turnstile-response']} />}
            <AuthSubmit processing={processing} pendingLabel="جاري إنشاء الحساب…">إنشاء الحساب</AuthSubmit>
        </form>
    </AuthLayout>;
}
