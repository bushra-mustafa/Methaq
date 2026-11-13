import { Head, Link } from '@inertiajs/react';
import AuthLayout from '../../Components/Auth/AuthLayout';
import AuthStatus from '../../Components/Auth/AuthStatus';
import AuthSubmit from '../../Components/Auth/AuthSubmit';
import useAuthForm from '../../Components/Auth/useAuthForm';

interface VerifyEmailProps { status?: string | null }

export default function VerifyEmail({ status }: VerifyEmailProps) {
    const { submit, processing } = useAuthForm('/email/verification-notification', {});

    return <AuthLayout title="تحققي من بريدك" description="أرسلنا رابط التحقق إلى بريدك. افتحيه لتأكيد عنوانك قبل نشر الدعوة." footer={<Link href="/logout" method="post" as="button">الخروج من الحساب</Link>}>
        <Head title="التحقق من البريد" />
        {status === 'verification-link-sent' && <AuthStatus>أرسلنا رابطاً جديداً إلى بريدك.</AuthStatus>}
        <form onSubmit={submit}>
            <AuthSubmit processing={processing}>أعيدي</AuthSubmit>
        </form>
    </AuthLayout>;
}
