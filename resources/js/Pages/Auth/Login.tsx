import { Head, Link } from '@inertiajs/react';
import AuthField from '../../Components/Auth/AuthField';
import AuthLayout from '../../Components/Auth/AuthLayout';
import AuthStatus from '../../Components/Auth/AuthStatus';
import AuthSubmit from '../../Components/Auth/AuthSubmit';
import PasswordField from '../../Components/Auth/PasswordField';
import useAuthForm from '../../Components/Auth/useAuthForm';

interface LoginProps { status?: string | null }

export default function Login({ status }: LoginProps) {
    const { form, submit, bind, processing } = useAuthForm('/login', { email: '', password: '', remember: false as boolean }, ['password']);

    return <AuthLayout title="أهلاً برجوعك" description="ادخلي لحفظ تصاميمك ومتابعة مناسباتك." footer={<p className="m-0">أول مرة هنا؟ <Link href="/register">أنشئي حسابك</Link></p>}>
        <Head title="تسجيل الدخول" />
        {status && <AuthStatus>{status}</AuthStatus>}
        <form className="flex flex-col gap-5" onSubmit={submit} noValidate>
            <AuthField label="البريد الإلكتروني" type="email" autoComplete="email" autoFocus required {...bind('email')} />
            <PasswordField label="كلمة المرور" autoComplete="current-password" required {...bind('password')} />
            <div className="flex flex-wrap items-center justify-between gap-x-6">
                <label className="inline-flex min-h-11 cursor-pointer items-center gap-3 text-[15px]">
                    <input type="checkbox" className="size-5 accent-methaq-primary" checked={form.data.remember} onChange={(event) => form.setData('remember', event.target.checked)} />
                    تذكريني
                </label>
                <Link href="/forgot-password" className="inline-flex min-h-11 items-center text-[15px] font-medium text-methaq-primary underline decoration-methaq-gold/60 underline-offset-8 hover:decoration-methaq-gold">نسيت كلمة المرور</Link>
            </div>
            <AuthSubmit processing={processing} pendingLabel="جاري الدخول…">دخول</AuthSubmit>
        </form>
    </AuthLayout>;
}
