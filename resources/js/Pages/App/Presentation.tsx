import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { InvitationPlayer } from '../../Domains/Editor/Components/InvitationPlayer';
import { InvitationPreview } from '../../Domains/Editor/Components/InvitationPreview';
import { ScenePanel } from '../../Domains/Editor/Components/ScenePanel';
import { useAutosave } from '../../Domains/Editor/Hooks/useAutosave';
import { useHistory } from '../../Domains/Editor/Hooks/useHistory';
import { usePresentationCard } from '../../Domains/Editor/Hooks/usePresentationCard';
import { parseDesignDocument } from '../../Domains/Editor/Services/DesignDocumentSerializer';
import type { DesignDocument } from '../../Types/DesignDocument';
import type { EditorAsset } from '../../Types/Editor';
import type { EventDTO } from '../../Types/EventDTO';
import '../../../css/editor.css';
import '../../../css/presentation.css';

interface Props { event: EventDTO; document: unknown; revision: number; assets: EditorAsset[] }

function PresentationWorkspace({ event, document, revision, assets }: Omit<Props, 'document'> & { document: DesignDocument }) {
    const history = useHistory(document);
    const autosave = useAutosave(`/app/events/${event.id}/design`, history.value, revision);
    const card = usePresentationCard(history.value, assets);
    const [fullPreview, setFullPreview] = useState(false);
    const sceneKey = JSON.stringify({ scene: history.value.scene, palette: history.value.palette });
    const useServerVersion = (): void => {
        const snapshot = autosave.takeServerVersion();
        if (snapshot) history.reset(snapshot.document);
    };
    const canPreview = card.ready && !card.error && card.cardUrl !== null;
    return <main className="presentation-page" dir="rtl">
        <Head title={`الظرف والفتح · ${event.title}`} />
        <header className="presentation-topbar">
            <Link href={`/app/events/${event.id}/editor`} onBefore={() => !autosave.hasUnsavedChanges} aria-disabled={autosave.hasUnsavedChanges} title={autosave.hasUnsavedChanges ? 'انتظري اكتمال الحفظ قبل الرجوع' : 'الرجوع إلى محرر البطاقة'}>→ الرجوع للبطاقة</Link>
            <img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="ميثاق" />
            <div className="presentation-topbar-actions"><span className={`is-${autosave.status}`} role="status">{autosave.message}</span><button type="button" onClick={history.undo} disabled={!history.canUndo}>تراجع</button><button type="button" onClick={history.redo} disabled={!history.canRedo}>إعادة</button></div>
        </header>
        <div className="presentation-content">
            <header className="presentation-heading"><div><p>{event.title}</p><h1>قبل أن تبدأ الحكاية</h1><p>اختاري الظرف، ضعي ختمك، وجرّبي اللحظة الأولى لفتح الدعوة.</p></div><button type="button" disabled={!canPreview} onClick={() => setFullPreview(true)}>معاينة كاملة ↗</button></header>
            <div className="presentation-layout">
                <div className="presentation-settings">
                    <ScenePanel assets={assets} cardUrl={card.cardUrl ?? undefined} scene={history.value.scene} palette={history.value.palette} onChange={(scene) => history.commit((current) => ({ ...current, scene }))} onPreview={() => setFullPreview(true)} previewReady={canPreview} />
                </div>
                <aside className="presentation-live-preview" aria-label="معاينة مباشرة للظرف">
                    <div className="presentation-preview-heading"><strong>هكذا تبدأ دعوتك</strong><span>تتحدث المعاينة مع اختياراتك</span></div>
                    {canPreview && card.cardUrl ? <InvitationPlayer audioAllowed={!fullPreview} assets={assets} cardParts={card.parts} manageFocus={false} key={sceneKey} scene={history.value.scene} palette={history.value.palette} cardUrl={card.cardUrl} title={event.title} viewport="phone" /> : <div className="presentation-loading" role={card.error ? 'alert' : 'status'}>{card.error || 'نجهّز معاينة بطاقتك…'}</div>}
                </aside>
            </div>
            <div className={`presentation-save-status is-${autosave.status}`} aria-live="polite">
                <strong>{autosave.message}</strong>
                {autosave.hasUnsavedChanges && <p>انتظري اكتمال الحفظ قبل الرجوع إلى محرر البطاقة.</p>}
                {autosave.status === 'error' && <button type="button" onClick={autosave.retry}>إعادة محاولة الحفظ</button>}
                {autosave.status === 'offline' && <p>سنحاول حفظ تعديلاتك عند رجوع الاتصال. أبقي الصفحة مفتوحة.</p>}
                {autosave.status === 'conflict' && <><p>تغيّر التصميم في صفحة أخرى. اختاري النسخة التي تريدين الاحتفاظ بها.</p><button type="button" onClick={useServerVersion}>استخدام نسخة الخادم</button><button type="button" onClick={autosave.keepLocalVersion}>الاحتفاظ بتعديلاتي</button></>}
            </div>
        </div>
        <div className="presentation-render-source" ref={card.host} aria-hidden="true" inert />
        {fullPreview && canPreview && card.cardUrl && <InvitationPreview assets={assets} cardParts={card.parts} scene={history.value.scene} palette={history.value.palette} cardUrl={card.cardUrl} title={event.title} onClose={() => setFullPreview(false)} />}
    </main>;
}

export default function Presentation(props: Props) {
    try {
        return <PresentationWorkspace {...props} document={parseDesignDocument(props.document)} />;
    } catch {
        return <main className="editor-contract-error" dir="rtl"><Head title="تعذّر عرض الظرف" /><h1>تعذّر تحميل التصميم</h1><p>بيانات البطاقة تحتاج مراجعة قبل ضبط العرض.</p><Link href={`/app/events/${props.event.id}`}>الرجوع للمناسبة</Link></main>;
    }
}
