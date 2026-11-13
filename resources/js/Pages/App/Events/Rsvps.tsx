import { Head, Link } from '@inertiajs/react';
import { AppShell } from '../../../Domains/Users/Components/AppShell';
import type { EventDTO, RsvpRow, RsvpStatus, RsvpSummary } from '../../../Types/EventDTO';
import '../../../../css/events.css';

interface RsvpsProps {
    event: EventDTO;
    summary: RsvpSummary;
    rsvps: RsvpRow[];
    truncated: boolean;
}

const statusLabels: Record<RsvpStatus, string> = { attending: '✓ سيحضر', declined: '✕ اعتذر', maybe: '؟ ربما' };

function formatTime(value: string | null, timezone: string): string {
    return value ? new Intl.DateTimeFormat('ar-LY', { dateStyle: 'medium', timeStyle: 'short', timeZone: timezone }).format(new Date(value)) : '';
}

export default function EventRsvps({ event, summary, rsvps, truncated }: RsvpsProps) {
    return <AppShell eyebrow={event.categoryLabel} title="ردود الضيوف">
        <Head title={`ردود ضيوف ${event.title}`} />
        <div className="rsvp-heading">
            <div><h2>{event.title}</h2><p>الردود تصلك من صفحة الدعوة المنشورة فقط، وأرقام الهواتف والملاحظات تظهر لك وحدك.</p></div>
            <div className="rsvp-actions">
                {summary.responses > 0 && <a className="account-primary" href={`/app/events/${event.id}/rsvps/export`} download>تنزيل الردود CSV</a>}
                <Link className="account-secondary" href={`/app/events/${event.id}`}>الرجوع إلى المناسبة</Link>
            </div>
        </div>
        <dl className="rsvp-summary">
            <div><dt>سيحضرون (مع المرافقين)</dt><dd>{summary.headcount}</dd><small>{summary.attending} ضيف و{summary.companions} مرافق</small></div>
            <div><dt>اعتذروا</dt><dd>{summary.declined}</dd></div>
            <div><dt>ربما</dt><dd>{summary.maybe}</dd></div>
            <div><dt>إجمالي الردود</dt><dd>{summary.responses}</dd></div>
        </dl>
        {rsvps.length === 0 ? <p className="rsvp-empty">لم يصلك أي رد بعد. شاركي رابط الدعوة مع ضيوفك وستظهر ردودهم هنا.</p> : <>
            {truncated && <p className="rsvp-empty" role="note">تُعرض أحدث {rsvps.length} رداً هنا. ملف CSV يحتوي كل الردود.</p>}
            <div className="rsvp-table-wrap">
                <table className="rsvp-table">
                    <caption className="sr-only">ردود ضيوف {event.title}</caption>
                    <thead><tr><th scope="col">الاسم</th><th scope="col">الحالة</th><th scope="col">المرافقون</th><th scope="col">الهاتف</th><th scope="col">ملاحظات</th><th scope="col">وقت الرد</th></tr></thead>
                    <tbody>{rsvps.map((rsvp) => <tr key={rsvp.id}>
                        <th scope="row">{rsvp.guestName}</th>
                        <td><span className={`rsvp-status is-${rsvp.status}`}>{statusLabels[rsvp.status]}</span></td>
                        <td>{rsvp.status === 'attending' ? rsvp.companions : '—'}</td>
                        <td dir="ltr">{rsvp.guestPhone ?? '—'}</td>
                        <td>{rsvp.notes ?? '—'}</td>
                        <td>{formatTime(rsvp.submittedAt, event.timezone)}</td>
                    </tr>)}</tbody>
                </table>
            </div>
        </>}
    </AppShell>;
}
