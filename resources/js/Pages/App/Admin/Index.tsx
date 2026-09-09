import { Head, Link } from '@inertiajs/react';
import { AppShell } from '../../../Domains/Users/Components/AppShell';

export default function AdminIndex() {
    return <AppShell eyebrow="إدارة ميثاق" title="لوحة التشغيل">
        <Head title="إدارة ميثاق" />
        <section className="account-section admin-foundation">
            <span className="dashboard-mark" aria-hidden="true">م</span>
            <div><h2>الوصول الإداري محمي</h2><p>هذه الصفحة لا تفتح إلا لمدير نشط فعّل التحقق بخطوتين. مكتبة القوالب والأصول جاهزة للمراجعة والتفعيل أو الإيقاف مع تسجيل السبب.</p><Link href="/app/admin/library">إدارة مكتبة القوالب</Link></div>
        </section>
    </AppShell>;
}
