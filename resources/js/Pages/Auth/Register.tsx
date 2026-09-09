import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AuthShell } from '../../Domains/Users/Components/AuthShell';
import { FormField } from '../../Domains/Users/Components/FormField';

export default function Register() {
    const form = useForm({ name: '', email: '', phone: '', password: '', password_confirmation: '' });
    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
    }

    return <AuthShell title="ابدئي دعوتك" description="حساب واحد يجمع تصاميمك وروابط مناسباتك." footer={<p>عندك حساب؟ <Link href="/login">سجلي الدخول</Link></p>}>
        <Head title="إنشاء حساب" />
        <form className="account-form" onSubmit={submit} noValidate>
            <FormField label="الاسم" name="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} autoComplete="name" autoFocus required error={form.errors.name} />
            <FormField label="البريد الإلكتروني" name="email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} autoComplete="email" required error={form.errors.email} />
            <FormField label="رقم الهاتف · اختياري" name="phone" type="tel" value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)} autoComplete="tel" error={form.errors.phone} />
            <FormField label="كلمة المرور" name="password" type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} autoComplete="new-password" required error={form.errors.password} />
            <p className="account-hint">12 حرفاً على الأقل، مع حرف كبير ورقم ورمز.</p>
            <FormField label="تأكيد كلمة المرور" name="password_confirmation" type="password" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} autoComplete="new-password" required error={form.errors.password_confirmation} />
            <button className="account-primary" type="submit" disabled={form.processing}>{form.processing ? 'جاري إنشاء الحساب…' : 'إنشاء الحساب'}</button>
        </form>
    </AuthShell>;
}
