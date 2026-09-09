import { Head, Link } from '@inertiajs/react';
import { DashboardEventCard } from '../../Domains/Events/Components/DashboardEventCard';
import { AppShell } from '../../Domains/Users/Components/AppShell';
import type { EventDTO, EventPresentationState } from '../../Types/EventDTO';
import '../../../css/events.css';

interface DashboardProps {
    user: { name: string; emailVerified: boolean };
    events: EventDTO[];
}

export default function Dashboard({ user, events }: DashboardProps) {
    const stateCounts = events.reduce<Record<EventPresentationState, number>>((counts, event) => ({ ...counts, [event.presentationState]: counts[event.presentationState] + 1 }), { draft: 0, preparing: 0, ready: 0, expired: 0 });
    return <AppShell eyebrow="مساحتك في ميثاق" title={`أهلاً، ${user.name}`}>
        <Head title="مناسباتي" />
        {!user.emailVerified && <aside className="account-banner"><div><strong>بريدك يحتاج تأكيداً</strong><p>يمكنك تجهيز المسودة، وسيطلب التأكيد قبل نشر الدعوة.</p></div><Link href="/email/verify">تأكيد البريد</Link></aside>}
        <section className="dashboard-hero">
            <div><span>دعواتك، على طريقتك</span><h2>{events.length === 0 ? 'ابدئي أول حكاية' : 'كل التفاصيل في مكان واحد'}</h2><p>{events.length === 0 ? 'اختاري قالباً، اكتبي تفاصيلك، ثم اصنعي لحظة فتح لا تُنسى.' : 'واصلي من حيث توقفتِ، وعدّلي بطاقاتك ومشهد افتتاح الدعوة.'}</p></div>
            <Link className="account-primary" href="/app/events/create">+ إنشاء مناسبة</Link>
        </section>
        {events.length > 0 && <section className="dashboard-summary" aria-label="ملخص المناسبات">
            <div><strong>{events.length}</strong><span>كل المناسبات</span></div><div><strong>{stateCounts.draft}</strong><span>مسودات تحتاج لمسة</span></div><div><strong>{stateCounts.ready}</strong><span>دعوات منشورة</span></div>
        </section>}
        {events.length === 0 ? <section className="dashboard-grid">
            <article className="dashboard-empty">
                <span className="dashboard-mark" aria-hidden="true">م</span>
                <h2>أول مناسبة تبدأ من هنا</h2>
                <p>اختاري قالباً أو ابدئي ببطاقة فارغة، ثم اجمعي الألوان والخطوط والتفاصيل بطريقتك.</p>
                <Link className="account-primary" href="/app/events/create">إنشاء مناسبة</Link>
            </article>
            <aside className="dashboard-next"><span>حسابك جاهز</span><h2>اختاري البداية المناسبة</h2><p>كل مناسبة تُنشأ كمسودة خاصة بك، مع رابط فريد وموعد انتهاء محسوب تلقائياً.</p><Link href="/templates">تصفحي القوالب</Link></aside>
        </section> : <section className="dashboard-events-section" aria-label="مناسباتي"><header><div><span>مناسباتك</span><h2>تابعي آخر تعديلاتك</h2></div><Link href="/templates">تصفّح القوالب</Link></header><div className="dashboard-events-grid">{events.map((event) => <DashboardEventCard event={event} key={event.id} />)}</div></section>}
    </AppShell>;
}
