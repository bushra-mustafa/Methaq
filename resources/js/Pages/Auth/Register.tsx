import { Head, Link } from '@inertiajs/react';
import { Mail, Phone, User } from 'lucide-react';
import AuthField from '../../Components/Auth/AuthField';
import AuthLayout from '../../Components/Auth/AuthLayout';
import AuthSubmit from '../../Components/Auth/AuthSubmit';
import PasswordField from '../../Components/Auth/PasswordField';
import useAuthForm from '../../Components/Auth/useAuthForm';

export default function Register() {
    const { submit, bind, processing } = useAuthForm('/register', { name: '', email: '', phone: '', password: '', password_confirmation: '' }, ['password', 'password_confirmation']);

    return <AuthLayout title="ابدئي دعوتك" description="حساب واحد يجمع تصاميمك وروابط مناسباتك، وتجرّبين قبل أن تدفعي." footer={<p className="m-0">عندك حساب؟ <Link href="/login">سجلي الدخول</Link></p>}>
        <Head title="إنشاء حساب" />
        <form className="flex flex-col gap-5" onSubmit={submit} noValidate>
            <AuthField icon={User} label="الاسم" autoComplete="name" autoFocus required {...bind('name')} />
            <AuthField icon={Mail} label="البريد الإلكتروني" type="email" autoComplete="email" required {...bind('email')} />
            <AuthField icon={Phone} label="رقم الهاتف (اختياري)" type="tel" autoComplete="tel" {...bind('phone')} />
            <PasswordField label="كلمة المرور" autoComplete="new-password" required hint="12 حرفاً على الأقل، مع حرف كبير ورقم ورمز." {...bind('password')} />
            <PasswordField label="تأكيد كلمة المرور" autoComplete="new-password" required {...bind('password_confirmation')} />
            <AuthSubmit processing={processing} pendingLabel="جاري إنشاء الحساب…">إنشاء الحساب</AuthSubmit>
        </form>
    </AuthLayout>;
}
