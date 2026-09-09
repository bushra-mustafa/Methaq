import { Link } from '@inertiajs/react';
import type { EventDTO, EventPresentationState } from '../../../Types/EventDTO';

const stateLabels: Record<EventPresentationState, string> = {
    draft: 'مسودة',
    preparing: 'قيد التجهيز',
    ready: 'منشورة',
    expired: 'منتهية',
};

function formatEventDate(event: EventDTO): string {
    return new Intl.DateTimeFormat('ar-LY', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: event.timezone,
    }).format(new Date(event.eventDate));
}

function nextStep(event: EventDTO): { label: string; href: string } {
    if (event.presentationState === 'draft') return { label: 'كمّلي التصميم', href: `/app/events/${event.id}/editor` };
    if (event.presentationState === 'ready') return { label: 'عرض الدعوة', href: `/app/events/${event.id}` };
    return { label: 'عرض المناسبة', href: `/app/events/${event.id}` };
}

export function DashboardEventCard({ event }: { event: EventDTO }) {
    const action = nextStep(event);
    return <article className="dashboard-event-card">
        <div className="dashboard-event-art">
            {event.template ? <img src={event.template.thumbnailUrl} alt={`معاينة ${event.template.name}`} /> : <div className="event-card-blank" aria-hidden="true">م</div>}
            <span className={`event-state is-${event.presentationState}`}>{stateLabels[event.presentationState]}</span>
        </div>
        <div className="dashboard-event-copy">
            <span>{event.categoryLabel}</span>
            <h2>{event.title}</h2>
            <time dateTime={event.eventDate}>{formatEventDate(event)}</time>
            <p dir="ltr">{event.subdomain}.methaq.link</p>
            <div className="dashboard-event-actions"><Link href={action.href}>{action.label} ←</Link><Link href={`/app/events/${event.id}`} aria-label={`تفاصيل ${event.title}`}>التفاصيل</Link></div>
        </div>
    </article>;
}
