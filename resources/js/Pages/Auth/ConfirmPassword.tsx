import { Head } from '@inertiajs/react';
import AuthLayout from '../../Components/Auth/AuthLayout';
import AuthSubmit from '../../Components/Auth/AuthSubmit';
import PasswordField from '../../Components/Auth/PasswordField';
import useAuthForm from '../../Components/Auth/useAuthForm';

export default function ConfirmPassword() {
    const { submit, bind, processing } = useAuthForm('/user/confirm-password', { password: '' }, ['password']);

    return <AuthLayout title="أكدي كلمة المرور" description="هذه الخطوة تحمي إعدادات الأمان الحساسة في حسابك.">
        <Head title="تأكيد كلمة المرور" />
        <form className="flex flex-col gap-5" onSubmit={submit} noValidate>
            <PasswordField label="كلمة المرور" autoComplete="current-password" autoFocus required {...bind('password')} />
            <AuthSubmit processing={processing}>تأكيد</AuthSubmit>
        </form>
    </AuthLayout>;
}
