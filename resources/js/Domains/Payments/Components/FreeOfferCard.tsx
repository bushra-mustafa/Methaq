import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import type { FreeOfferDTO } from '../../../Types/FreeOffer';
import { isOpen, remainingLabel } from '../Services/offerTime';

/** The free offer on the payment page: what it is, how long and how many places are left, and one clear button. */
export function FreeOfferCard({ eventId, offer }: { eventId: string; offer: FreeOfferDTO }) {
    const form = useForm({});
    const [now, setNow] = useState(() => Date.now());
    const errors: Partial<Record<string, string>> = form.errors;

    useEffect(() => {
        const timer = window.setInterval(() => setNow(Date.now()), 60_000);
        return () => window.clearInterval(timer);
    }, []);

    if (!isOpen(offer.endsAt, now)) return null;

    function claim(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        form.post(`/app/events/${eventId}/free-offer`, { preserveScroll: true });
    }

    return <section className="free-offer-card" aria-labelledby="free-offer-title">
        <span className="free-offer-badge">عرض مجاني</span>
        <h2 id="free-offer-title">{offer.title}</h2>
        {offer.description && <p>{offer.description}</p>}
        <p className="free-offer-meta"><strong>بقي {offer.spotsLeft} من {offer.spotsTotal} مقاعد</strong><span>· تنتهي بعد {remainingLabel(offer.endsAt, now)}</span></p>
        <form onSubmit={claim}>
            {errors.offer && <p className="account-error" role="alert">{errors.offer}</p>}
            <button className="account-primary" type="submit" disabled={form.processing} aria-busy={form.processing}>{form.processing ? 'جارٍ التفعيل…' : 'فعّلي مناسبتي مجاناً'}</button>
            <small>دون دفع ودون رفع قسيمة. تُفعَّل مناسبتك فوراً وتنشرينها من صفحة الظرف والفتح.</small>
        </form>
    </section>;
}
