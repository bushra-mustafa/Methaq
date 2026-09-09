import { Head } from '@inertiajs/react';
import { AppShell } from '../../../Domains/Users/Components/AppShell';

export default function AdminIndex() {
    return <AppShell eyebrow="إدارة ميثاق" title="لوحة التشغيل">
        <Head title="إدارة ميثاق" />
        <section className="account-section admin-foundation">
            <span className="dashboard-mark" aria-hidden="true">م</span>
            <div><h2>الوصول الإداري محمي</h2><p>هذه الصفحة لا تفتح إلا لمدير نشط فعّل التحقق بخطوتين. أدوات المستخدمين والقوالب والتصيير ستضاف في مراحلها المعتمدة.</p></div>
        </section>
    </AppShell>;
}
