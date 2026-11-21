import { pngFileFromDataUrl } from '../../Editor/Services/pngFileFromDataUrl';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { LoadingState } from '../../../Components/Loading/Spinner';
import type { SharedPageProps } from '../../../Types/PageProps';
import type { PublishStateDTO } from '../../../Types/PublishDTO';

interface PublishPanelProps {
    eventId: string;
    publish: PublishStateDTO;
    /** PNG data URL of the rendered card, or null while it is being prepared. */
    cardUrl: string | null;
    /** Latest revision the server has saved; the server rejects a stale one. */
    revision: number;
    saved: boolean;
}

/** Publishing: the browser sends the rendered card, the server re-checks everything. */
export function PublishPanel({ eventId, publish, cardUrl, revision, saved }: PublishPanelProps) {
    const { errors } = usePage<SharedPageProps>().props;
    const [confirming, setConfirming] = useState(false);
    const [sending, setSending] = useState(false);
    const [localError, setLocalError] = useState<string | null>(null);
    const [copied, setCopied] = useState(false);
    const ready = publish.canPublish && cardUrl !== null && saved && !sending;
    const serverError = errors.publish ?? errors.render ?? errors.expectedRevision ?? null;

    function submit(): void {
        if (cardUrl === null) return;
        setSending(true);
        setLocalError(null);
        try {
            router.post(
                `/app/events/${eventId}/publish`,
                { render: pngFileFromDataUrl(cardUrl), expectedRevision: revision },
                { forceFormData: true, preserveScroll: true, onFinish: () => { setSending(false); setConfirming(false); } },
            );
        } catch {
            setSending(false);
            setLocalError('تعذّر تجهيز صورة البطاقة. حدّثي الصفحة وحاولي مرة أخرى.');
        }
    }

    async function copyLink(): Promise<void> {
        try {
            await navigator.clipboard.writeText(publish.invitationUrl);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 2500);
        } catch {
            setLocalError('تعذّر النسخ تلقائياً. حدّدي الرابط وانسخيه يدوياً.');
        }
    }

    if (publish.state === 'published') {
        return <section className="presentation-publish is-published" aria-labelledby="publish-title">
            <h2 id="publish-title">دعوتك منشورة</h2>
            <p>شاركي هذا الرابط مع ضيوفك. يُغلق تلقائياً بعد المناسبة.</p>
            <div className="presentation-publish-link">
                <input readOnly dir="ltr" value={publish.invitationUrl} aria-label="رابط الدعوة" onFocus={(event) => event.currentTarget.select()} />
                <button type="button" onClick={() => void copyLink()}>{copied ? 'تم النسخ ✓' : 'نسخ الرابط'}</button>
                <a href={publish.invitationUrl} target="_blank" rel="noreferrer">فتح الدعوة</a>
            </div>
            <p role="status">{copied ? 'نُسخ رابط الدعوة.' : ''}</p>
            {localError && <p className="presentation-publish-error" role="alert">{localError}</p>}
        </section>;
    }

    return <section className="presentation-publish" aria-labelledby="publish-title">
        <h2 id="publish-title">نشر الدعوة</h2>
        {publish.state === 'blocked' && <p role="status">{publish.blocker}</p>}
        {publish.state === 'ready' && <>
            <p>عند النشر تُحفظ نسخة الدعوة الحالية ويصبح الرابط متاحاً لضيوفك. بعد النشر يُقفل تعديل التصميم، ولأي تعديل لاحق راسلي فريق ميثاق.</p>
            {publish.willRemove.length > 0 && <p className="presentation-publish-warning" role="note">لن تظهر في النسخة المنشورة (غير متاحة في قالبك): {publish.willRemove.join('، ')}.</p>}
            {!saved && <p role="status">انتظري اكتمال الحفظ قبل النشر.</p>}
            {saved && cardUrl === null && <LoadingState message="نجهّز صورة البطاقة…" />}
            {!confirming
                ? <button type="button" disabled={!ready} onClick={() => setConfirming(true)}>نشر الدعوة</button>
                : <div className="presentation-publish-confirm" role="group" aria-label="تأكيد النشر">
                    <strong>هل تريدين نشر الدعوة الآن؟</strong>
                    <button type="button" disabled={!ready} onClick={submit}>{sending ? 'جاري النشر…' : 'نعم، انشري'}</button>
                    <button type="button" disabled={sending} onClick={() => setConfirming(false)}>إلغاء</button>
                </div>}
        </>}
        {(serverError ?? localError) && <p className="presentation-publish-error" role="alert">{serverError ?? localError}</p>}
    </section>;
}
