import { useForm } from '@inertiajs/react';
import Honeypot from '../../../Components/Honeypot';
import { useId } from 'react';

type AttendingStatus = 'attending' | 'declined' | 'maybe';

interface RsvpFormData {
    submission_token: string;
    guest_name: string;
    guest_phone: string;
    attending_status: AttendingStatus;
    companions_count: number;
    notes: string;
    website: string;
}

const submissionToken = (): string => crypto.randomUUID();

export function RsvpForm() {
    const formId = useId();
    const form = useForm<RsvpFormData>({
        submission_token: submissionToken(),
        guest_name: '',
        guest_phone: '',
        attending_status: 'attending',
        companions_count: 0,
        notes: '',
        website: '',
    });
    const isDeclined = form.data.attending_status === 'declined';
    const submit = (event: React.FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        form.post('/rsvp', { preserveScroll: true });
    };

    if (form.recentlySuccessful) {
        return <section className="rsvp-confirmation" aria-live="polite"><span>✓</span><h2>وصل تأكيدك</h2><p>شكراً لمشاركتنا هذه المناسبة الجميلة.</p></section>;
    }

    return <section className="rsvp-form" aria-labelledby={`${formId}-title`}>
        <header><span>تأكيد الحضور</span><h2 id={`${formId}-title`}>هل ستشاركنا الفرحة؟</h2><p>نحتاج تفاصيل بسيطة لتنظيم حضورك.</p></header>
        <form onSubmit={submit} noValidate>
            <label htmlFor={`${formId}-name`}>الاسم الكامل<input id={`${formId}-name`} value={form.data.guest_name} onChange={(event) => form.setData('guest_name', event.target.value)} autoComplete="name" required /></label>
            {form.errors.guest_name && <p className="rsvp-error">{form.errors.guest_name}</p>}
            <label htmlFor={`${formId}-phone`}>رقم الهاتف <small>اختياري</small><input id={`${formId}-phone`} value={form.data.guest_phone} onChange={(event) => form.setData('guest_phone', event.target.value)} autoComplete="tel" inputMode="tel" /></label>
            {form.errors.guest_phone && <p className="rsvp-error">{form.errors.guest_phone}</p>}
            <fieldset><legend>حالة الحضور</legend><div className="rsvp-options">
                <label><input type="radio" name={`${formId}-status`} checked={form.data.attending_status === 'attending'} onChange={() => form.setData('attending_status', 'attending')} />سأحضر</label>
                <label><input type="radio" name={`${formId}-status`} checked={form.data.attending_status === 'maybe'} onChange={() => form.setData('attending_status', 'maybe')} />ربما</label>
                <label><input type="radio" name={`${formId}-status`} checked={isDeclined} onChange={() => form.setData({ attending_status: 'declined', companions_count: 0 })} />لن أتمكن</label>
            </div></fieldset>
            {!isDeclined && <label htmlFor={`${formId}-companions`}>عدد المرافقين<select id={`${formId}-companions`} value={form.data.companions_count} onChange={(event) => form.setData('companions_count', Number(event.target.value))}>{[0, 1, 2, 3, 4, 5].map((count) => <option key={count} value={count}>{count}</option>)}</select></label>}
            <label htmlFor={`${formId}-notes`}>رسالة لأصحاب المناسبة <small>اختياري</small><textarea id={`${formId}-notes`} value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} maxLength={1000} rows={3} /></label>
            {form.errors.notes && <p className="rsvp-error">{form.errors.notes}</p>}
            <Honeypot value={form.data.website} onChange={(event) => form.setData('website', event.target.value)} />
            <button type="submit" disabled={form.processing} aria-busy={form.processing}>{form.processing ? 'يجري الإرسال…' : 'تأكيد الرد'}</button>
        </form>
    </section>;
}
