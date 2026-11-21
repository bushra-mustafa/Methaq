import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { LoadingState } from '../../../Components/Loading/Spinner';
import { pngFileFromDataUrl } from '../../Editor/Services/pngFileFromDataUrl';
import type { SharedPageProps } from '../../../Types/PageProps';
import type { PublishedRefreshStateDTO } from '../../../Types/PublishDTO';

interface RefreshPublishedPanelProps {
    eventId: string;
    refresh: PublishedRefreshStateDTO;
    /** PNG data URL of the rendered card, or null while it is being prepared. */
    cardUrl: string | null;
    /** Latest revision the server has saved; the server rejects a stale one. */
    revision: number;
    saved: boolean;
}

/** Studio only: an administrator replaces the live invitation with the saved draft. The server re-checks everything. */
export function RefreshPublishedPanel({ eventId, refresh, cardUrl, revision, saved }: RefreshPublishedPanelProps) {
    const { errors, flash } = usePage<SharedPageProps>().props;
    const [confirming, setConfirming] = useState(false);
    const [sending, setSending] = useState(false);
    const [localError, setLocalError] = useState<string | null>(null);
    const ready = refresh.canRefresh && cardUrl !== null && saved && !sending;
    const serverError = errors.publish ?? errors.render ?? errors.expectedRevision ?? null;

    function submit(): void {
        if (cardUrl === null) return;
        setSending(true);
        setLocalError(null);
        try {
            router.post(
                `/app/studio/events/${eventId}/refresh`,
                { render: pngFileFromDataUrl(cardUrl), expectedRevision: revision },
                { forceFormData: true, preserveScroll: true, onFinish: () => { setSending(false); setConfirming(false); } },
            );
        } catch {
            setSending(false);
            setLocalError('تعذّر تجهيز صورة البطاقة. حدّثي الصفحة وحاولي مرة أخرى.');
        }
    }

    return <section className="presentation-publish" aria-labelledby="refresh-title">
        <h2 id="refresh-title">تحديث الدعوة المنشورة</h2>
        {flash.status === 'invitation-refreshed' && <p role="status">حُدّثت الدعوة المنشورة. يرى الضيوف التصميم الجديد عند فتح الرابط.</p>}
        {refresh.blocker !== null && <p role="status">{refresh.blocker}</p>}
        {refresh.canRefresh && <>
            <p>يرى الضيوف الآن النسخة {refresh.publishedRevision ?? '—'}. التحديث ينقل المسودة المحفوظة (النسخة {revision}) إلى الرابط نفسه، دون تغيير موعد الانتهاء.</p>
            {refresh.willRemove.length > 0 && <p className="presentation-publish-warning" role="note">لن تظهر في النسخة المنشورة (غير متاحة لهذه المناسبة): {refresh.willRemove.join('، ')}.</p>}
            {!saved && <p role="status">انتظري اكتمال الحفظ قبل التحديث.</p>}
            {saved && cardUrl === null && <LoadingState message="نجهّز صورة البطاقة…" />}
            {!confirming
                ? <button type="button" disabled={!ready} onClick={() => setConfirming(true)}>تحديث الدعوة المنشورة</button>
                : <div className="presentation-publish-confirm" role="group" aria-label="تأكيد التحديث">
                    <strong>هل تريدين استبدال النسخة التي يراها الضيوف الآن؟</strong>
                    <button type="button" disabled={!ready} onClick={submit}>{sending ? 'جاري التحديث…' : 'نعم، حدّثي الدعوة'}</button>
                    <button type="button" disabled={sending} onClick={() => setConfirming(false)}>إلغاء</button>
                </div>}
            <a href={refresh.invitationUrl} target="_blank" rel="noreferrer">فتح الدعوة كما يراها الضيوف ↗</a>
        </>}
        {(serverError ?? localError) && <p className="presentation-publish-error" role="alert">{serverError ?? localError}</p>}
    </section>;
}
