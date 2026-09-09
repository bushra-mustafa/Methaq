import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AuthShell } from '../../Domains/Users/Components/AuthShell';
import { FormField } from '../../Domains/Users/Components/FormField';

export default function ConfirmPassword() {
    const form = useForm({ password: '' });
    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        form.post('/user/confirm-password', { onFinish: () => form.reset('password') });
    }

    return <AuthShell title="أكدي كلمة المرور" description="هذه الخطوة تحمي إعدادات الأمان الحساسة في حسابك.">
        <Head title="تأكيد كلمة المرور" />
        <form className="account-form" onSubmit={submit} noValidate>
            <FormField label="كلمة المرور" name="password" type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} autoComplete="current-password" autoFocus required error={form.errors.password} />
            <button className="account-primary" type="submit" disabled={form.processing}>{form.processing ? 'جاري التأكيد…' : 'تأكيد ومتابعة'}</button>
        </form>
    </AuthShell>;
}
