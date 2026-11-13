import Thumbnail from '../../../Components/Thumbnail';
import { Head, Link, usePage } from '@inertiajs/react';
import { paymentStateLabels } from '../../../Domains/Payments/Services/paymentState';
import { AppShell } from '../../../Domains/Users/Components/AppShell';
import type { EventDTO, EventPresentationState, RsvpSummary } from '../../../Types/EventDTO';
import type { SharedPageProps } from '../../../Types/PageProps';
import type { EventPaymentDTO } from '../../../Types/PaymentDTO';
import '../../../../css/events.css';

interface ShowEventProps {
    event: EventDTO;
    payment: EventPaymentDTO;
    rsvpSummary: RsvpSummary;
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

export default function ShowEvent({ event, payment, rsvpSummary }: ShowEventProps) {
    const { flash } = usePage<SharedPageProps>().props;

    return <AppShell eyebrow={event.categoryLabel} title={event.title}>
        <Head title={event.title} />
        {flash.status === 'event-published' && <aside className="event-success" role="status"><strong>نُشرت دعوتك</strong><span>الرابط متاح الآن لضيوفك.</span></aside>}
        {flash.status === 'event-created' && <aside className="event-success" role="status"><strong>تم إنشاء المناسبة</strong><span>حُفظت كمسودة خاصة بك وأصبح رابطها محجوزاً.</span></aside>}
        <section className="event-overview">
            <div className="event-overview-preview">{event.template ? <Thumbnail src={event.template.thumbnailUrl} avif={event.template.thumbnailAvifUrl} webp={event.template.thumbnailWebpUrl} alt={`معاينة قالب ${event.template.name}`} width={540} height={960} /> : <div className="event-card-blank" aria-hidden="true">م</div>}</div>
            <div className="event-overview-copy"><span className={`event-state is-${event.presentationState}`}>{stateLabels[event.presentationState]}</span><dl><div><dt>الموعد</dt><dd>{formatDate(event.eventDate, event.timezone)}</dd></div><div><dt>انتهاء الرابط</dt><dd>{formatDate(event.expiresAt, event.timezone)}</dd></div><div><dt>الرابط المحجوز</dt><dd dir="ltr">{event.status === 'published' ? <a href={event.invitationUrl} target="_blank" rel="noreferrer">{event.invitationUrl}</a> : event.invitationUrl}</dd></div><div><dt>بداية التصميم</dt><dd>{event.template?.name ?? 'بطاقة فارغة'}</dd></div></dl><aside className="event-next-step"><strong>المسودة جاهزة للتخصيص</strong><p>ابدئي بمحتوى البطاقة، ثم جهّزي لحظة فتحها للضيوف.</p></aside><div className="event-overview-actions"><Link className="account-primary" href={`/app/events/${event.id}/editor`}>تخصيص البطاقة</Link><Link className="account-secondary" href="/app">الرجوع إلى المناسبات</Link></div><section className="event-presentation-callout" aria-labelledby="presentation-callout-title"><div><span>02 · تجربة الضيف</span><h2 id="presentation-callout-title">الظرف والافتتاح</h2><p>اختاري شكل الظرف، الخلفية المحيطة بالبطاقة، الحركة والصوت، ثم شاهدي النتيجة كما يراها الضيف.</p></div><ul><li>ظرف وختم قابلان للتخصيص</li><li>خلفية اختيارية مستقلة عن البطاقة</li><li>حركة وصوت للّحظة الأولى</li></ul><Link href={`/app/events/${event.id}/presentation`}>جهّزي تجربة الدعوة ←</Link></section><section className="event-payment-callout" aria-labelledby="payment-callout-title"><div><span>03 · الدفع</span><h2 id="payment-callout-title">{paymentStateLabels[payment.state]}</h2><p>{payment.state === 'paid' ? 'اعتُمد الدفع لهذه المناسبة.' : payment.state === 'pending_review' ? 'نراجع القسيمة بعد التأكد من وصول المبلغ.' : `حوّلي ${payment.priceLabel ?? 'المبلغ'} بالتحويل البنكي، ثم ارفعي صورة القسيمة.`}</p></div><Link href={`/app/events/${event.id}/payment`}>{payment.canSubmit ? 'الدفع ورفع القسيمة ←' : 'تفاصيل الدفع ←'}</Link></section><section className="event-payment-callout" aria-labelledby="rsvp-callout-title"><div><span>04 · الردود</span><h2 id="rsvp-callout-title">{rsvpSummary.responses === 0 ? 'لا توجد ردود بعد' : `${rsvpSummary.headcount} ${rsvpSummary.headcount === 1 ? 'شخص' : 'أشخاص'} سيحضرون`}</h2><p>{rsvpSummary.responses === 0 ? 'تظهر هنا ردود ضيوفك بعد نشر الدعوة.' : `${rsvpSummary.responses} ${rsvpSummary.responses === 1 ? 'رد' : 'ردود'}، منها ${rsvpSummary.declined} اعتذار.`}</p></div><Link href={`/app/events/${event.id}/rsvps`}>ردود الضيوف ←</Link></section></div>
        </section>
    </AppShell>;
}
