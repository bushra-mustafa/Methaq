import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AuthShell } from '../../Domains/Users/Components/AuthShell';
import { FormField } from '../../Domains/Users/Components/FormField';

interface ResetPasswordProps { email: string; token: string }

export default function ResetPassword({ email, token }: ResetPasswordProps) {
    const form = useForm({ token, email, password: '', password_confirmation: '' });
    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') });
    }

    return <AuthShell title="كلمة مرور جديدة" description="اختاري كلمة قوية لا تستخدمينها في حساب آخر." footer={<Link href="/login">العودة إلى الدخول</Link>}>
        <Head title="تعيين كلمة مرور جديدة" />
        <form className="account-form" onSubmit={submit} noValidate>
            <FormField label="البريد الإلكتروني" name="email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} autoComplete="email" required error={form.errors.email} />
            <FormField label="كلمة المرور الجديدة" name="password" type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} autoComplete="new-password" autoFocus required error={form.errors.password} />
            <FormField label="تأكيد كلمة المرور" name="password_confirmation" type="password" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} autoComplete="new-password" required error={form.errors.password_confirmation} />
            <button className="account-primary" type="submit" disabled={form.processing}>{form.processing ? 'جاري الحفظ…' : 'حفظ كلمة المرور'}</button>
        </form>
    </AuthShell>;
}
