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
    const ready = publish.canPublish && cardUrl !== null && saved && !sending;
    const serverError = errors.publish ?? errors.render ?? errors.expectedRevision ?? null;

    async function submit(): Promise<void> {
        if (cardUrl === null) return;
        setSending(true);
        setLocalError(null);
        try {
            const blob = await (await fetch(cardUrl)).blob();
            router.post(
                `/app/events/${eventId}/publish`,
                { render: new File([blob], 'card.png', { type: 'image/png' }), expectedRevision: revision },
                { forceFormData: true, preserveScroll: true, onFinish: () => { setSending(false); setConfirming(false); } },
            );
        } catch {
            setSending(false);
            setLocalError('تعذّر تجهيز صورة البطاقة. حدّثي الصفحة وحاولي مرة أخرى.');
        }
    }

    if (publish.state === 'published') {
        return <section className="presentation-publish is-published" aria-labelledby="publish-title">
            <h2 id="publish-title">دعوتك منشورة</h2>
            <p>شاركي هذا الرابط مع ضيوفك. يُغلق تلقائياً بعد المناسبة.</p>
            <a href={publish.invitationUrl} dir="ltr" target="_blank" rel="noreferrer">{publish.invitationUrl}</a>
        </section>;
    }

    return <section className="presentation-publish" aria-labelledby="publish-title">
        <h2 id="publish-title">نشر الدعوة</h2>
        {publish.state === 'blocked' && <p role="status">{publish.blocker}</p>}
        {publish.state === 'ready' && <>
            <p>عند النشر تُحفظ نسخة الدعوة الحالية ويصبح الرابط متاحاً لضيوفك. تعديلاتك بعد النشر تبقى في المسودة ولا تغيّر النسخة المنشورة.</p>
            {publish.willRemove.length > 0 && <p className="presentation-publish-warning" role="note">لن تظهر في النسخة المنشورة (غير متاحة في قالبك): {publish.willRemove.join('، ')}.</p>}
            {!saved && <p role="status">انتظري اكتمال الحفظ قبل النشر.</p>}
            {saved && cardUrl === null && <LoadingState message="نجهّز صورة البطاقة…" />}
            {!confirming
                ? <button type="button" disabled={!ready} onClick={() => setConfirming(true)}>نشر الدعوة</button>
                : <div className="presentation-publish-confirm" role="group" aria-label="تأكيد النشر">
                    <strong>هل تريدين نشر الدعوة الآن؟</strong>
                    <button type="button" disabled={!ready} onClick={() => void submit()}>{sending ? 'جاري النشر…' : 'نعم، انشري'}</button>
                    <button type="button" disabled={sending} onClick={() => setConfirming(false)}>إلغاء</button>
                </div>}
        </>}
        {(serverError ?? localError) && <p className="presentation-publish-error" role="alert">{serverError ?? localError}</p>}
    </section>;
}
