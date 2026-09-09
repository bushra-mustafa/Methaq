import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AuthShell } from '../../Domains/Users/Components/AuthShell';
import { FormField } from '../../Domains/Users/Components/FormField';

interface ForgotPasswordProps { status?: string | null }

export default function ForgotPassword({ status }: ForgotPasswordProps) {
    const form = useForm({ email: '' });
    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        form.post('/forgot-password');
    }

    return <AuthShell title="استعادة كلمة المرور" description="أدخلي بريدك وسنرسل لك رابطاً آمناً لإعادة التعيين." footer={<Link href="/login">العودة إلى تسجيل الدخول</Link>}>
        <Head title="استعادة كلمة المرور" />
        {status && <p className="account-status" role="status">إذا كان البريد مسجلاً، سيصلك رابط الاستعادة.</p>}
        <form className="account-form" onSubmit={submit} noValidate>
            <FormField label="البريد الإلكتروني" name="email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} autoComplete="email" autoFocus required error={form.errors.email} />
            <button className="account-primary" type="submit" disabled={form.processing}>{form.processing ? 'جاري الإرسال…' : 'إرسال رابط الاستعادة'}</button>
        </form>
    </AuthShell>;
}
