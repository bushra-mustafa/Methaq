import { Head, Link } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { DesignProposal, ProposalPartId } from '../../../Types/DesignProposal';
import { useHistory } from '../../../Domains/Editor/Hooks/useHistory';
import { useIvoryCanvas } from '../../../Domains/Editor/Proposals/Ivory/Hooks/useIvoryCanvas';
import { useProposalDraft } from '../../../Domains/Editor/Proposals/Ivory/Hooks/useProposalDraft';
import { IvoryInvitation } from '../../../Domains/Editor/Proposals/Ivory/Components/IvoryInvitation';
import { ProposalElementList } from '../../../Domains/Editor/Proposals/Ivory/Components/ProposalElementList';
import { ProposalInspector } from '../../../Domains/Editor/Proposals/Ivory/Components/ProposalInspector';
import { moveProposalLayer, parseProposal, updateProposalPart } from '../../../Domains/Editor/Proposals/Ivory/Services/proposalDocument';
import { downloadProposalFile, readProposalFile } from '../../../Domains/Editor/Proposals/Ivory/Services/proposalFiles';
import '../../../../css/ivory-proposal.css';

interface Props { document: unknown; revision: number }

function ProposalWorkspace({ initial, revision }: { initial: DesignProposal; revision: number }) {
    const history = useHistory(initial);
    const [selected, setSelected] = useState<ProposalPartId | null>(null);
    const [preview, setPreview] = useState(false);
    const [panel, setPanel] = useState<'elements' | 'properties'>('elements');
    const [mobileOpen, setMobileOpen] = useState(false);
    const [fileError, setFileError] = useState('');
    const dialog = useRef<HTMLDialogElement>(null);
    const draft = useProposalDraft(history.value, revision, history.reset);
    const move = useCallback((id: ProposalPartId, dx: number, dy: number) => {
        history.commit((doc) => updateProposalPart(doc, id, (part) => ({ ...part, x: Math.max(-1800, Math.min(1800, Math.round(part.x + dx))), y: Math.max(-5000, Math.min(5000, Math.round(part.y + dy))) })));
    }, [history.commit]);
    const select = useCallback((id: ProposalPartId | null) => { setSelected(id); if (id) setPanel('properties'); }, []);
    const canvas = useIvoryCanvas({ document: history.value, selected, preview, onSelect: select, onMove: move });
    const part = history.value.parts.find((part) => part.id === selected);
    const toggle = (id: ProposalPartId) => history.commit((doc) => updateProposalPart(doc, id, (part) => ({ ...part, visible: !part.visible })));
    const download = () => downloadProposalFile(JSON.stringify(history.value, null, 2), 'methaq-ivory-draft.json', 'application/json');
    const exportPng = () => {
        try {
            const url = canvas.capture();
            if (!url) return;
            const link = document.createElement('a'); link.href = url; link.download = 'methaq-ivory-preview.png'; link.click();
        } catch { setFileError('تعذّر تصدير الصورة. قللي حجم الصور المرفوعة ثم أعيدي المحاولة.'); }
    };

    useEffect(() => {
        if (mobileOpen) dialog.current?.showModal();
        else dialog.current?.close();
    }, [mobileOpen]);
    useEffect(() => {
        const keyboard = (event: KeyboardEvent) => {
            if (preview || event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement || event.target instanceof HTMLSelectElement) return;
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') { event.preventDefault(); event.shiftKey ? history.redo() : history.undo(); return; }
            if (!selected || !['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(event.key)) return;
            event.preventDefault();
            const step = event.shiftKey ? 10 : 1;
            move(selected, event.key === 'ArrowRight' ? step : event.key === 'ArrowLeft' ? -step : 0, event.key === 'ArrowDown' ? step : event.key === 'ArrowUp' ? -step : 0);
        };
        window.addEventListener('keydown', keyboard);
        return () => window.removeEventListener('keydown', keyboard);
    }, [selected, preview, move, history.undo, history.redo]);

    const controls = <>
        <div className="ivory-panel-tabs"><button type="button" aria-pressed={panel === 'elements'} onClick={() => setPanel('elements')}>العناصر</button><button type="button" aria-pressed={panel === 'properties'} disabled={!part} onClick={() => setPanel('properties')}>التفاصيل</button></div>
        {panel === 'elements' ? <><p className="ivory-panel-help">اختاري عنصراً أو مجموعة. النصوص والصور وأجزاء الظرف كلها مستقلة.</p><ProposalElementList document={history.value} selected={selected} onSelect={select} onToggle={toggle} /></> :
            part ? <ProposalInspector key={part.id} part={part} onChange={(next) => history.commit((doc) => updateProposalPart(doc, next.id, () => next))} onSelect={select} onReorder={(direction) => history.commit((doc) => moveProposalLayer(doc, part.id, direction))} /> : <p>اختاري عنصراً لتعديل تفاصيله.</p>}
        <details className="ivory-draft-files"><summary>نسخة من المسودة</summary><p>نزّلي إعدادات العناصر للرجوع لها أو لاستبدال البيانات لاحقاً.</p><button type="button" onClick={download}>تنزيل ملف المسودة</button><label>استيراد مسودة<input type="file" accept=".json,application/json" onChange={(event) => { const file = event.target.files?.[0]; if (!file) return; setFileError(''); void readProposalFile(file).then((document) => history.commit(() => document)).catch((error: unknown) => setFileError(error instanceof Error ? error.message : 'ملف غير صالح.')); event.target.value = ''; }} /></label></details>
    </>;
    return <main className={'ivory-proposal-page' + (preview ? ' is-preview' : '')} dir="rtl">
        <Head title="اقتراح عاجي · دعوة زفاف" />
        <header className="ivory-proposal-topbar">
            <div className="ivory-proposal-brand"><Link href="/app" onBefore={() => !draft.dirty} title={draft.dirty ? 'انتظري اكتمال حفظ المسودة قبل المغادرة' : 'الرجوع إلى لوحة ميثاق'}>→</Link><img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="ميثاق" /><span>مسودة اقتراح</span></div>
            <div className="ivory-proposal-actions"><span className="ivory-save-status" role="status">{draft.status}</span><button type="button" disabled={draft.saving || !draft.dirty || Boolean(draft.conflict)} onClick={() => void draft.save()}>حفظ المسودة</button><button type="button" disabled={!history.canUndo || preview} aria-label="تراجع" onClick={history.undo}>↶</button><button type="button" disabled={!history.canRedo || preview} aria-label="إعادة" onClick={history.redo}>↷</button><button className="ivory-preview-button" type="button" onClick={() => setPreview((value) => !value)}>{preview ? 'رجوع للتعديل' : 'معاينة الدعوة'}</button></div>
        </header>
        <section className="ivory-proposal-heading"><div><p>مجموعة ميثاق · اقتراح جديد</p><h1>حكاية بالعاجي</h1><span>دعوة كلاسيكية، بتفاصيل تشبهكما.</span></div><div className="ivory-proposal-tools"><button className="ivory-mobile-open" type="button" onClick={() => setMobileOpen(true)}>العناصر والتفاصيل ☷</button><button type="button" disabled={!canvas.ready || Boolean(canvas.error)} onClick={exportPng}>تنزيل المعاينة PNG ↓</button><small>مسودة خاصة · لا تنشر رابطاً</small></div></section>
        {(draft.error || fileError || draft.conflict) && <aside className="ivory-notice" role="alert">{draft.error || fileError || 'توجد تعديلات أحدث لهذه المسودة. نزّلي نسختك قبل تحميل نسخة الخادم.'}{draft.conflict && <><button type="button" onClick={download}>تنزيل نسختي</button><button type="button" onClick={draft.restoreServer}>تحميل نسخة الخادم</button></>}</aside>}
        <div className="ivory-proposal-layout">
            <div className="ivory-proposal-stage"><IvoryInvitation host={canvas.host} ready={canvas.ready} error={canvas.error} retry={canvas.retry} /><p className="ivory-proposal-caption">{preview ? 'معاينة الاقتراح الكامل' : 'اختاري عنصراً واسحبيه، أو عدّلي تفاصيله من القائمة.'}</p></div>
            {!preview && <aside className="ivory-proposal-sidebar" aria-label="لوحة تحرير الاقتراح">{controls}</aside>}
        </div>
        <dialog className="ivory-mobile-panel" aria-label="لوحة عناصر الدعوة" ref={dialog} onCancel={() => setMobileOpen(false)} onClose={() => setMobileOpen(false)}><header><strong>عناصر الدعوة</strong><button type="button" onClick={() => setMobileOpen(false)} aria-label="إغلاق لوحة العناصر">×</button></header>{mobileOpen && controls}</dialog>
    </main>;
}

export default function IvoryWedding({ document, revision }: Props) {
    try { return <ProposalWorkspace initial={parseProposal(document)} revision={revision} />; }
    catch { return <main className="ivory-contract-error" dir="rtl"><h1>تعذّر فتح المسودة</h1><p>بيانات الاقتراح تحتاج مراجعة. بقيت نسختك المحفوظة كما هي.</p><Link href="/app">الرجوع إلى ميثاق</Link></main>; }
}
