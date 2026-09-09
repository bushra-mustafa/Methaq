import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AuthShell } from '../../Domains/Users/Components/AuthShell';

interface VerifyEmailProps { status?: string | null }

export default function VerifyEmail({ status }: VerifyEmailProps) {
    const form = useForm({});
    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        form.post('/email/verification-notification');
    }

    return <AuthShell title="تحققي من بريدك" description="أرسلنا رابط التحقق إلى بريدك. افتحيه لتأكيد عنوانك قبل نشر الدعوة." footer={<Link href="/logout" method="post" as="button">الخروج من الحساب</Link>}>
        <Head title="التحقق من البريد" />
        {status === 'verification-link-sent' && <p className="account-status" role="status">أرسلنا رابطاً جديداً إلى بريدك.</p>}
        <form className="account-form" onSubmit={submit}>
            <button className="account-primary" type="submit" disabled={form.processing}>{form.processing ? 'جاري الإرسال…' : 'إعادة إرسال رابط التحقق'}</button>
        </form>
    </AuthShell>;
}
