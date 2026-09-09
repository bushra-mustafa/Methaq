import { Head, Link } from '@inertiajs/react';
import { AppShell } from '../../Domains/Users/Components/AppShell';

interface DashboardProps { user: { name: string; emailVerified: boolean } }

export default function Dashboard({ user }: DashboardProps) {
    return <AppShell eyebrow="لوحة المناسبات" title={`أهلاً، ${user.name}`}>
        <Head title="مناسباتي" />
        {!user.emailVerified && <aside className="account-banner"><div><strong>بريدك يحتاج تأكيداً</strong><p>يمكنك تجهيز المسودة، وسيطلب التأكيد قبل نشر الدعوة.</p></div><Link href="/email/verify">تأكيد البريد</Link></aside>}
        <section className="dashboard-grid">
            <article className="dashboard-empty">
                <span className="dashboard-mark" aria-hidden="true">م</span>
                <h2>أول مناسبة تبدأ من هنا</h2>
                <p>اختاري قالباً أو ابدئي ببطاقة فارغة، ثم اجمعي الألوان والخطوط والتفاصيل بطريقتك.</p>
                <button type="button" disabled title="ستتاح مع مرحلة إنشاء المناسبات">إنشاء مناسبة قريباً</button>
            </article>
            <aside className="dashboard-next"><span>الخطوة الحالية</span><h2>حسابك جاهز للتصميم</h2><p>إدارة المناسبات والقوالب تأتي في المرحلة التالية من البناء.</p><Link href="/app/profile">راجعي أمان الحساب</Link></aside>
        </section>
    </AppShell>;
}
