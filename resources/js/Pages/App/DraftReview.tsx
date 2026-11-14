import { Head } from '@inertiajs/react';
import { BackLink } from '../../Domains/Editor/Components/BackLink';
import { LoadingState } from '../../Components/Loading/Spinner';
import { InvitationPlayer } from '../../Domains/Editor/Components/InvitationPlayer';
import { usePresentationCard } from '../../Domains/Editor/Hooks/usePresentationCard';
import { parseDesignDocument } from '../../Domains/Editor/Services/DesignDocumentSerializer';
import type { DesignDocument } from '../../Types/DesignDocument';
import type { EditorAsset } from '../../Types/Editor';
import type { EventDTO } from '../../Types/EventDTO';
import '../../../css/editor.css';
import '../../../css/presentation.css';

interface Props { event: EventDTO; owner: { name: string; email: string }; document: unknown; revision: number; assets: EditorAsset[] }

const statusLabels: Record<EventDTO['status'], string> = { draft: 'مسودة', published: 'منشورة', expired: 'منتهية' };

function DraftWorkspace({ event, owner, revision, assets, document }: Omit<Props, 'document'> & { document: DesignDocument }) {
    const card = usePresentationCard(document, assets);
    const ready = card.ready && !card.error && card.cardUrl !== null;
    const sceneKey = JSON.stringify({ scene: document.scene, palette: document.palette });

    return <main className="presentation-page" dir="rtl">
        <Head title={`مراجعة مسودة · ${event.title}`} />
        <header className="presentation-topbar">
            <BackLink href="/admin/events">→ الرجوع إلى المناسبات</BackLink>
            <img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="ميثاق" />
            <span />
        </header>
        <div className="presentation-content">
            <p className="account-status" role="note">مراجعة للقراءة فقط. هذه مسودة {owner.name} ولا يمكن تعديلها من هنا، ويُسجَّل فتحك لها في سجل التدقيق.</p>
            <header className="presentation-heading"><div><p>{event.categoryLabel} · {statusLabels[event.status]} · النسخة {revision}</p><h1>{event.title}</h1><p>{owner.name} · <bdi dir="ltr">{owner.email}</bdi></p></div></header>
            <div className="presentation-layout">
                <section aria-label="البطاقة">
                    {ready && card.cardUrl ? <img src={card.cardUrl} alt={`بطاقة ${event.title}`} style={{ width: '100%', maxWidth: 420, height: 'auto', borderRadius: 8 }} /> : card.error ? <div className="presentation-loading" role="alert">{card.error}</div> : <LoadingState message="نجهّز البطاقة…" />}
                </section>
                <aside className="presentation-live-preview" aria-label="معاينة الظرف">
                    <div className="presentation-preview-heading"><strong>الظرف والفتح</strong><span>كما يراه الضيف</span></div>
                    {ready && card.cardUrl ? <InvitationPlayer audioAllowed={false} assets={assets} cardParts={card.parts} manageFocus={false} key={sceneKey} scene={document.scene} palette={document.palette} cardUrl={card.cardUrl} title={event.title} viewport="phone" /> : null}
                </aside>
            </div>
        </div>
        <div className="presentation-render-source" ref={card.host} aria-hidden="true" inert />
    </main>;
}

export default function DraftReview(props: Props) {
    try {
        return <DraftWorkspace {...props} document={parseDesignDocument(props.document)} />;
    } catch {
        return <main className="editor-contract-error" dir="rtl"><Head title="تعذّر عرض المسودة" /><h1>تعذّر تحميل التصميم</h1><p>بيانات هذه المسودة تحتاج مراجعة.</p><BackLink href="/admin/events">الرجوع إلى المناسبات</BackLink></main>;
    }
}
