import { Head, Link } from '@inertiajs/react';
import AuthField from '../../Components/Auth/AuthField';
import AuthLayout from '../../Components/Auth/AuthLayout';
import AuthSubmit from '../../Components/Auth/AuthSubmit';
import PasswordField from '../../Components/Auth/PasswordField';
import useAuthForm from '../../Components/Auth/useAuthForm';

interface ResetPasswordProps { email: string; token: string }

export default function ResetPassword({ email, token }: ResetPasswordProps) {
    const { submit, bind, processing } = useAuthForm('/reset-password', { token, email, password: '', password_confirmation: '' }, ['password', 'password_confirmation']);

    return <AuthLayout title="كلمة مرور جديدة" description="اختاري كلمة قوية لا تستخدمينها في حساب آخر." footer={<Link href="/login">العودة إلى الدخول</Link>}>
        <Head title="تعيين كلمة مرور جديدة" />
        <form className="flex flex-col gap-5" onSubmit={submit} noValidate>
            <AuthField label="البريد الإلكتروني" type="email" autoComplete="email" required {...bind('email')} />
            <PasswordField label="كلمة المرور الجديدة" autoComplete="new-password" autoFocus required {...bind('password')} />
            <PasswordField label="تأكيد كلمة المرور" autoComplete="new-password" required {...bind('password_confirmation')} />
            <AuthSubmit processing={processing} pendingLabel="جاري الحفظ…">حفظ كلمة المرور</AuthSubmit>
        </form>
    </AuthLayout>;
}
