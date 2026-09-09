import { Head, Link, usePage } from '@inertiajs/react';
import { AppShell } from '../../../Domains/Users/Components/AppShell';
import type { EventDTO, EventPresentationState } from '../../../Types/EventDTO';
import type { SharedPageProps } from '../../../Types/PageProps';
import '../../../../css/events.css';

interface ShowEventProps {
    event: EventDTO;
}

const stateLabels: Record<EventPresentationState, string> = {
    draft: 'مسودة خاصة',
    preparing: 'قيد تجهيز النشر',
    ready: 'منشورة',
    expired: 'منتهية',
};

function formatDate(value: string, timezone: string): string {
    return new Intl.DateTimeFormat('ar-LY', { dateStyle: 'full', timeStyle: 'short', timeZone: timezone }).format(new Date(value));
}

export default function ShowEvent({ event }: ShowEventProps) {
    const { flash } = usePage<SharedPageProps>().props;

    return <AppShell eyebrow={event.categoryLabel} title={event.title}>
        <Head title={event.title} />
        {flash.status === 'event-created' && <aside className="event-success" role="status"><strong>تم إنشاء المناسبة</strong><span>حُفظت كمسودة خاصة بك وأصبح رابطها محجوزاً.</span></aside>}
        <section className="event-overview">
            <div className="event-overview-preview">{event.template ? <img src={event.template.thumbnailUrl} alt={`معاينة ${event.template.name}`} /> : <div className="event-card-blank" aria-hidden="true">م</div>}</div>
            <div className="event-overview-copy"><span className={`event-state is-${event.presentationState}`}>{stateLabels[event.presentationState]}</span><dl><div><dt>الموعد</dt><dd>{formatDate(event.eventDate, event.timezone)}</dd></div><div><dt>انتهاء الرابط</dt><dd>{formatDate(event.expiresAt, event.timezone)}</dd></div><div><dt>الرابط المحجوز</dt><dd dir="ltr">{event.subdomain}.methaq.link</dd></div><div><dt>بداية التصميم</dt><dd>{event.template?.name ?? 'بطاقة فارغة'}</dd></div></dl><aside className="event-next-step"><strong>المسودة جاهزة للتخصيص</strong><p>افتحي المحرر واجمعي النصوص والإطارات والألوان كما تريدين.</p></aside><div className="event-overview-actions"><Link className="account-primary" href={`/app/events/${event.id}/editor`}>تخصيص البطاقة</Link><Link className="account-secondary" href="/app">الرجوع إلى المناسبات</Link></div></div>
        </section>
    </AppShell>;
}
