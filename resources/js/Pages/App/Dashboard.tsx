import { Head, Link } from '@inertiajs/react';
import { AppShell } from '../../Domains/Users/Components/AppShell';
import type { EventDTO, EventPresentationState } from '../../Types/EventDTO';
import '../../../css/events.css';

interface DashboardProps {
    user: { name: string; emailVerified: boolean };
    events: EventDTO[];
}

const stateLabels: Record<EventPresentationState, string> = {
    draft: 'مسودة',
    preparing: 'قيد التجهيز',
    ready: 'منشورة',
    expired: 'منتهية',
};

function formatEventDate(event: EventDTO): string {
    return new Intl.DateTimeFormat('ar-LY', {
        dateStyle: 'long',
        timeStyle: 'short',
        timeZone: event.timezone,
    }).format(new Date(event.eventDate));
}

export default function Dashboard({ user, events }: DashboardProps) {
    return <AppShell eyebrow="لوحة المناسبات" title={`أهلاً، ${user.name}`}>
        <Head title="مناسباتي" />
        {!user.emailVerified && <aside className="account-banner"><div><strong>بريدك يحتاج تأكيداً</strong><p>يمكنك تجهيز المسودة، وسيطلب التأكيد قبل نشر الدعوة.</p></div><Link href="/email/verify">تأكيد البريد</Link></aside>}
        <div className="events-toolbar"><div><strong>{events.length}</strong><span>{events.length === 1 ? 'مناسبة' : 'مناسبات'}</span></div><Link className="account-primary" href="/app/events/create">إنشاء مناسبة</Link></div>
        {events.length === 0 ? <section className="dashboard-grid">
            <article className="dashboard-empty">
                <span className="dashboard-mark" aria-hidden="true">م</span>
                <h2>أول مناسبة تبدأ من هنا</h2>
                <p>اختاري قالباً أو ابدئي ببطاقة فارغة، ثم اجمعي الألوان والخطوط والتفاصيل بطريقتك.</p>
                <Link className="account-primary" href="/app/events/create">إنشاء مناسبة</Link>
            </article>
            <aside className="dashboard-next"><span>حسابك جاهز</span><h2>اختاري البداية المناسبة</h2><p>كل مناسبة تُنشأ كمسودة خاصة بك، مع رابط فريد وموعد انتهاء محسوب تلقائياً.</p><Link href="/templates">تصفحي القوالب</Link></aside>
        </section> : <section className="events-grid" aria-label="مناسباتي">
            {events.map((event) => <article className="event-card" key={event.id}>
                {event.template ? <img src={event.template.thumbnailUrl} alt="" /> : <div className="event-card-blank" aria-hidden="true">م</div>}
                <div className="event-card-copy"><div className="event-card-meta"><span>{event.categoryLabel}</span><strong className={`event-state is-${event.presentationState}`}>{stateLabels[event.presentationState]}</strong></div><h2>{event.title}</h2><time dateTime={event.eventDate}>{formatEventDate(event)}</time><p dir="ltr">{event.subdomain}.methaq.link</p><Link href={`/app/events/${event.id}`}>عرض المناسبة</Link></div>
            </article>)}
        </section>}
    </AppShell>;
}
