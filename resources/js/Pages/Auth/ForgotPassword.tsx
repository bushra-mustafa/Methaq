import { Head, Link } from '@inertiajs/react';
import AuthField from '../../Components/Auth/AuthField';
import AuthLayout from '../../Components/Auth/AuthLayout';
import AuthStatus from '../../Components/Auth/AuthStatus';
import AuthSubmit from '../../Components/Auth/AuthSubmit';
import useAuthForm from '../../Components/Auth/useAuthForm';

interface ForgotPasswordProps { status?: string | null }

export default function ForgotPassword({ status }: ForgotPasswordProps) {
    const { submit, bind, processing } = useAuthForm('/forgot-password', { email: '' });

    return <AuthLayout title="استعادة كلمة المرور" description="أدخلي بريدك وسنرسل لك رابطاً آمناً لإعادة التعيين." footer={<Link href="/login">العودة إلى تسجيل الدخول</Link>}>
        <Head title="استعادة كلمة المرور" />
        {status && <AuthStatus>إذا كان البريد مسجلاً، سيصلك رابط الاستعادة.</AuthStatus>}
        <form className="flex flex-col gap-5" onSubmit={submit} noValidate>
            <AuthField label="البريد الإلكتروني" type="email" autoComplete="email" autoFocus required {...bind('email')} />
            <AuthSubmit processing={processing} pendingLabel="جاري الإرسال…">إرسال رابط الاستعادة</AuthSubmit>
        </form>
    </AuthLayout>;
}
