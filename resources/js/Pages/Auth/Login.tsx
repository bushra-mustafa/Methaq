import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AuthShell } from '../../Domains/Users/Components/AuthShell';
import { FormField } from '../../Domains/Users/Components/FormField';

interface LoginProps { status?: string | null }

export default function Login({ status }: LoginProps) {
    const form = useForm({ email: '', password: '', remember: false });
    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        form.post('/login', { onFinish: () => form.reset('password') });
    }

    return <AuthShell title="أهلاً برجوعك" description="ادخلي لحفظ تصاميمك ومتابعة مناسباتك." footer={<p>أول مرة هنا؟ <Link href="/register">أنشئي حسابك</Link></p>}>
        <Head title="تسجيل الدخول" />
        {status && <p className="account-status" role="status">{status}</p>}
        <form className="account-form" onSubmit={submit} noValidate>
            <FormField label="البريد الإلكتروني" name="email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} autoComplete="email" autoFocus required error={form.errors.email} />
            <FormField label="كلمة المرور" name="password" type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} autoComplete="current-password" required error={form.errors.password} />
            <div className="account-form-row"><label className="account-check"><input type="checkbox" checked={form.data.remember} onChange={(event) => form.setData('remember', event.target.checked)} />تذكريني</label><Link href="/forgot-password">نسيت كلمة المرور</Link></div>
            <button className="account-primary" type="submit" disabled={form.processing}>{form.processing ? 'جاري الدخول…' : 'دخول'}</button>
        </form>
    </AuthShell>;
}
