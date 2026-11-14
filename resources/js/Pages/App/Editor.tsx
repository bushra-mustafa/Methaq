import { EventJourney } from '../../Domains/Events/Components/EventJourney';
import type { PresentationCard } from '../../Types/PresentationCard';
import { Head, Link } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { EditorControlPanel } from '../../Domains/Editor/Components/EditorControlPanel';
import { EditorMobilePanel } from '../../Domains/Editor/Components/EditorMobilePanel';
import { InvitationPreview } from '../../Domains/Editor/Components/InvitationPreview';
import { EditorInspector } from '../../Domains/Editor/Components/EditorInspector';
import { EditorLayersPanel } from '../../Domains/Editor/Components/EditorLayersPanel';
import { EditorLibraryPanel } from '../../Domains/Editor/Components/EditorLibraryPanel';
import { EditorPalettePanel } from '../../Domains/Editor/Components/EditorPalettePanel';
import { EditorMaterialPanel } from '../../Domains/Editor/Components/EditorMaterialPanel';
import { updateCardBackground, updateCardMaterial } from '../../Domains/Editor/Services/cardMaterials';
import { resolveColor } from '../../Domains/Editor/Services/editorDocument';
import { EditorTextPanel } from '../../Domains/Editor/Components/EditorTextPanel';
import { EditorSuggestionsPanel } from '../../Domains/Editor/Components/EditorSuggestionsPanel';
import { uploadTemplateThumbnail } from '../../Domains/Editor/Services/templateThumbnailClient';
import { watermarkPreview } from '../../Domains/Editor/Services/previewWatermark';
import { suggestionContent, type TextSuggestion } from '../../Domains/Editor/Services/textSuggestions';
import { useCanvas } from '../../Domains/Editor/Hooks/useCanvas';
import { useAutosave } from '../../Domains/Editor/Hooks/useAutosave';
import { useDesignHistory } from '../../Domains/Editor/Hooks/useDesignHistory';
import { parseDesignDocument } from '../../Domains/Editor/Services/DesignDocumentSerializer';
import { addAsset, addCollection, addText, layerWarnings, moveLayer, removeLayer, replaceImageAsset, updateLayer, updatePaletteColor } from '../../Domains/Editor/Services/editorDocument';
import type { DesignDocument } from '../../Types/DesignDocument';
import type { EditorAsset, EditorCollection, EditorPanel, EditorTemplate } from '../../Types/Editor';
import type { EventDTO } from '../../Types/EventDTO';
import type { Layer, TextLayer } from '../../Types/Layer';
import type { HexColor, PaletteRole } from '../../Types/Palette';
import '../../../css/editor.css';

interface EditorProps {
    /** Present when an owner designs an event's card. */
    event?: EventDTO;
    /** Present, with mode 'template', when an administrator designs a library template. */
    template?: EditorTemplate;
    mode?: 'template';
    document: unknown;
    revision: number;
    assets: EditorAsset[];
    collections: EditorCollection[];
    recommendedAssetIds: string[];
}

function acceptsShortcut(target: EventTarget | null): boolean {
    return !(target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement || target instanceof HTMLSelectElement || (target instanceof HTMLElement && target.isContentEditable));
}

/** What the editor is working on: an owner's event card, or a library template in the studio. */
interface EditorSubject {
    kind: 'event' | 'template';
    id: string;
    title: string;
    category: EventDTO['category'];
    categoryLabel: string;
    saveUrl: string;
    backHref: string;
    backLabel: string;
    exportName: string;
    event: EventDTO | null;
}

function buildSubject(props: EditorProps): EditorSubject | null {
    if (props.mode === 'template' && props.template) {
        const template = props.template;
        return { kind: 'template', id: String(template.id), title: template.name, category: template.category, categoryLabel: template.categoryLabel, saveUrl: `/app/studio/templates/${template.id}/design`, backHref: '/admin/templates', backLabel: 'الرجوع إلى القوالب', exportName: `template-${template.id}`, event: null };
    }
    if (props.event) {
        const event = props.event;
        return { kind: 'event', id: event.id, title: event.title, category: event.category, categoryLabel: event.categoryLabel, saveUrl: `/app/events/${event.id}/design`, backHref: `/app/events/${event.id}`, backLabel: 'الرجوع إلى المناسبة', exportName: `preview-${event.id}`, event };
    }
    return null;
}

function EditorWorkspace({ subject, document: initialDocument, revision, assets, collections, recommendedAssetIds }: Omit<EditorProps, 'document' | 'event' | 'template' | 'mode'> & { subject: EditorSubject; document: DesignDocument }) {
    const history = useDesignHistory(initialDocument);
    const autosave = useAutosave(subject.saveUrl, history.value, revision);
    const [thumbnailStatus, setThumbnailStatus] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
    const [selectedLayerId, setSelectedLayerId] = useState<string | null>(null);
    const [preview, setPreview] = useState<{ cardUrl: string; parts: PresentationCard | null; document: DesignDocument } | null>(null);
    const [panel, setPanel] = useState<EditorPanel>('layers');
    const [tab, setTab] = useState<'elements' | 'properties'>('elements');
    const [mobileOpen, setMobileOpen] = useState(false);
    const closeMobile = useCallback(() => setMobileOpen(false), []);
    const selectLayer = useCallback((id: string | null): void => {
        setSelectedLayerId(id);
        if (id) setTab('properties');
    }, []);
    const selectedLayer = history.value.canvas.layers.find((layer) => layer.id === selectedLayerId) ?? null;
    const sidebar = useRef<HTMLElement>(null);
    useEffect(() => { sidebar.current?.scrollTo({ top: 0 }); }, [selectedLayerId]);
    const fonts = useMemo(() => assets.filter((asset) => asset.type === 'font'), [assets]);
    const visualAssets = useMemo(() => assets.filter((asset) => asset.type !== 'font' && asset.type !== 'audio'), [assets]);
    const placedAssetIds = useMemo(() => history.value.canvas.layers.flatMap((layer) => layer.type === 'image' ? [layer.asset.assetId] : []), [history.value]);
    const assetMap = useMemo(() => new Map(assets.map((asset) => [asset.id, asset])), [assets]);

    const changeSelected = (update: (layer: Layer) => Layer): void => {
        if (!selectedLayerId) return;
        history.commit((document) => updateLayer(document, selectedLayerId, update));
    };
    const changeText = (update: (layer: TextLayer) => TextLayer): void => changeSelected((layer) => layer.type === 'text' ? update(layer) : layer);
    const canvasState = useCanvas({
        document: history.value,
        assets,
        selectedLayerId,
        onSelect: selectLayer,
        onLayerChange: (id, layer) => history.commit((document) => updateLayer(document, id, () => layer)),
    });

    useEffect(() => {
        if (selectedLayerId && !history.value.canvas.layers.some((layer) => layer.id === selectedLayerId)) setSelectedLayerId(null);
    }, [history.value, selectedLayerId]);

    useEffect(() => {
        const onKeyDown = (keyboard: KeyboardEvent): void => {
            if (preview || !acceptsShortcut(keyboard.target)) return;
            const modifier = keyboard.metaKey || keyboard.ctrlKey;
            if (modifier && keyboard.key.toLocaleLowerCase('en') === 'z') {
                keyboard.preventDefault();
                if (keyboard.shiftKey) history.redo(); else history.undo();
                return;
            }
            if (!selectedLayerId) return;
            if (keyboard.key === 'Delete' || keyboard.key === 'Backspace') {
                keyboard.preventDefault();
                history.commit((document) => removeLayer(document, selectedLayerId));
                return;
            }
            const offsets: Partial<Record<string, [number, number]>> = { ArrowUp: [0, -1], ArrowDown: [0, 1], ArrowRight: [1, 0], ArrowLeft: [-1, 0] };
            const offset = offsets[keyboard.key];
            if (offset && selectedLayer && !selectedLayer.locked) {
                keyboard.preventDefault();
                const step = keyboard.shiftKey ? 10 : 1;
                const [x, y] = offset;
                changeSelected((layer) => ({ ...layer, frame: { ...layer.frame, x: layer.frame.x + x * step, y: layer.frame.y + y * step } }));
            }
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [history, selectedLayer, selectedLayerId, preview]);

    const showPreview = (): void => {
        const cardUrl = canvasState.capturePreview();
        if (cardUrl) setPreview({ cardUrl, parts: canvasState.captureParts(), document: history.value });
    };

    const addVisualAsset = (asset: EditorAsset): void => history.commit((document) => addAsset(document, asset));
    const addAssetCollection = (collection: EditorCollection): void => history.commit((document) => addCollection(document, collection, assetMap));
    const removeSelected = (): void => {
        if (!selectedLayerId) return;
        history.commit((document) => removeLayer(document, selectedLayerId));
        setSelectedLayerId(null);
    };
    const warnings = layerWarnings(history.value, selectedLayer);
    const takeServerVersion = (): void => {
        const snapshot = autosave.takeServerVersion();
        if (!snapshot) return;
        history.reset(snapshot.document);
        setSelectedLayerId(null);
    };

    const goBack = (): boolean => !autosave.hasUnsavedChanges;
    const exportPng = (): void => {
        const url = canvasState.capturePreview();
        if (!url) return;
        void watermarkPreview(url).then((marked) => {
            const link = document.createElement('a');
            link.href = marked;
            link.download = `methaq-${subject.exportName}.png`;
            link.click();
        });
    };
    const saveThumbnail = (): void => {
        const url = canvasState.capturePreview();
        if (!url || subject.kind !== 'template') return;
        setThumbnailStatus('saving');
        void watermarkPreview(url, 540, 'subtle')
            .then((marked) => uploadTemplateThumbnail(subject.id, marked))
            .then(() => setThumbnailStatus('saved'))
            .catch(() => setThumbnailStatus('error'));
    };
    const addTextLayer = (font: EditorAsset): void => {
        const next = addText(history.value, font);
        history.commit(() => next);
        const added = next.canvas.layers.at(-1);
        if (added) selectLayer(added.id);
    };
    const insertSuggestion = (suggestion: TextSuggestion, font: EditorAsset): void => {
        const content = suggestionContent(suggestion);
        const length = Array.from(content).length;
        const verse = suggestion.kind === 'verse';
        const fontSize = verse ? (length > 200 ? 40 : length > 120 ? 46 : 52) : (length > 40 ? 46 : 54);
        const charactersPerLine = Math.max(8, Math.floor(840 / (fontSize * 0.45)));
        const lines = Math.ceil(length / charactersPerLine) + (verse ? 1 : 0);
        const next = addText(history.value, font, { content, fontSize, frame: { x: 120, y: 700, width: 840, height: Math.round(lines * fontSize * 1.7) } });
        history.commit(() => next);
        const added = next.canvas.layers.at(-1);
        if (added) selectLayer(added.id);
    };
    const controls = <EditorControlPanel tab={tab} tool={panel} hasSelection={Boolean(selectedLayer)} onTab={setTab} onTool={setPanel}>
        {tab === 'elements' ? <>
            {panel === 'layers' && <EditorLayersPanel layers={history.value.canvas.layers} assets={assets} selectedId={selectedLayerId} onSelect={selectLayer} onToggleVisibility={(id) => history.commit((document) => updateLayer(document, id, (layer) => ({ ...layer, visible: !layer.visible })))} />}
            {panel === 'elements' && <EditorLibraryPanel assets={assets} collections={collections} recommendedAssetIds={recommendedAssetIds} placedAssetIds={placedAssetIds} onAddAsset={addVisualAsset} onAddCollection={addAssetCollection} />}
            {panel === 'text' && <EditorTextPanel mode="add" fonts={fonts} selected={null} onAdd={addTextLayer} onChange={changeText} />}
            {panel === 'phrases' && <EditorSuggestionsPanel category={subject.category} fonts={fonts} onInsert={insertSuggestion} />}
            {panel === 'colors' && <EditorPalettePanel palette={history.value.palette} onChange={(role: PaletteRole, color: HexColor) => history.commit((document) => updatePaletteColor(document, role, color))} />}
            {panel === 'material' && <EditorMaterialPanel material={history.value.canvas.material} background={resolveColor(history.value.canvas.background, history.value)} onChange={(material) => history.commit((document) => updateCardMaterial(document, material))} onColorChange={(color) => history.commit((document) => updateCardBackground(document, color))} />}
        </> : selectedLayer ? <div className="editor-details">
            <div className="editor-selection-options">
                <label><input type="checkbox" checked={selectedLayer.visible} onChange={() => changeSelected((layer) => ({ ...layer, visible: !layer.visible }))} />إظهار العنصر</label>
                <label><input type="checkbox" checked={selectedLayer.locked} onChange={() => changeSelected((layer) => ({ ...layer, locked: !layer.locked }))} />قفل الحركة</label>
            </div>
            {selectedLayer.type === 'text' && <EditorTextPanel mode="edit" fonts={fonts} selected={selectedLayer} onAdd={addTextLayer} onChange={changeText} />}
            <EditorInspector selected={selectedLayer} replacementAssets={visualAssets} onChange={changeSelected} onReplace={(asset) => { if (selectedLayerId) history.commit((document) => replaceImageAsset(document, selectedLayerId, asset)); }} />
            <div className="editor-detail-order"><button type="button" onClick={() => history.commit((document) => moveLayer(document, selectedLayer.id, 'forward'))}>للأمام ↑</button><button type="button" onClick={() => history.commit((document) => moveLayer(document, selectedLayer.id, 'backward'))}>للخلف ↓</button></div>
            <button className="editor-delete-selected" type="button" onClick={removeSelected}>حذف العنصر المحدد</button>
        </div> : <p className="editor-empty">اختاري عنصراً من البطاقة أو من قائمة العناصر لتعديل تفاصيله.</p>}
    </EditorControlPanel>;

    return <main className="editor-page" dir="rtl">
        <Head title={subject.kind === 'template' ? `استوديو القالب ${subject.title}` : `تصميم ${subject.title}`} />
        <header className="editor-topbar">
            <div className="editor-brand"><Link href={subject.backHref} onBefore={goBack} aria-label={subject.backLabel} title={autosave.hasUnsavedChanges ? 'انتظري اكتمال الحفظ قبل المغادرة' : subject.backLabel} aria-disabled={autosave.hasUnsavedChanges}>→</Link><img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="ميثاق" /><span>{subject.kind === 'template' ? 'استوديو القوالب' : 'تصميم البطاقة'}</span></div>
            <div className="editor-history-actions">
                <span className={`editor-save-status is-${autosave.status}`} title={`نسخة التصميم ${autosave.revision}`} role="status">{autosave.message}</span>
                <button type="button" disabled={!autosave.hasUnsavedChanges || autosave.status === 'saving' || autosave.status === 'conflict'} onClick={autosave.retry}>حفظ التعديلات</button>
                <button className="editor-history-icon" type="button" disabled={!history.canUndo} onClick={history.undo} aria-label="تراجع" title="تراجع">↶</button>
                <button className="editor-history-icon" type="button" disabled={!history.canRedo} onClick={history.redo} aria-label="إعادة" title="إعادة">↷</button>
                <button className="editor-preview-button" type="button" disabled={!canvasState.ready || Boolean(canvasState.error)} onClick={showPreview}>معاينة الدعوة</button>
            </div>
        </header>
        {subject.event && <EventJourney event={subject.event} current="card" canLeave={!autosave.hasUnsavedChanges} />}
        {subject.kind === 'template' && <aside className="editor-notice editor-studio-note" role="note">أنتِ تعدّلين قالباً في المكتبة. تظهر التعديلات في المناسبات الجديدة فقط، والمناسبات القائمة تحتفظ بنسختها. احفظي صورة المعاينة بعد الانتهاء.</aside>}
        <section className="editor-heading">
            <div><p>ميثاق · {subject.categoryLabel}</p><h1>{subject.title}</h1><span>{subject.kind === 'template' ? 'قالب في مكتبة ميثاق.' : 'بطاقتك، بتفاصيل تشبهك.'}</span></div>
            <div className="editor-heading-tools">
                <button className="editor-mobile-open" type="button" aria-haspopup="dialog" onClick={() => setMobileOpen(true)}>العناصر والتفاصيل ☷</button>
                <button type="button" disabled={!canvasState.ready || Boolean(canvasState.error)} onClick={exportPng}>تنزيل المعاينة PNG ↓</button>
                {subject.kind === 'template' ? <button className="editor-next" type="button" disabled={!canvasState.ready || Boolean(canvasState.error) || autosave.hasUnsavedChanges || thumbnailStatus === 'saving'} onClick={saveThumbnail}>{thumbnailStatus === 'saving' ? 'جارٍ حفظ المعاينة…' : thumbnailStatus === 'saved' ? 'حُفظت المعاينة ✓' : thumbnailStatus === 'error' ? 'تعذّر الحفظ، حاولي مجدداً' : 'حفظ صورة المعاينة'}</button> : <Link className="editor-next" href={`/app/events/${subject.id}/presentation`} onBefore={goBack} aria-disabled={autosave.hasUnsavedChanges} title={autosave.hasUnsavedChanges ? 'انتظري اكتمال الحفظ قبل الانتقال' : 'اختيار الظرف وتجربة الفتح'}>التالي: اختيار الظرف ←</Link>}
            </div>
        </section>
        {history.error && <aside className="editor-notice" role="alert">{history.error}<button type="button" onClick={history.dismissError}>إغلاق التنبيه</button></aside>}
        {['offline', 'error', 'conflict'].includes(autosave.status) && <aside className="editor-notice" role="alert">
            <strong>{autosave.message}</strong>
            {autosave.status === 'offline' && <p>التعديلات ما زالت داخل هذه الصفحة. أبقيها مفتوحة حتى يرجع الاتصال ويكتمل الحفظ.</p>}
            {autosave.status === 'error' && <button type="button" onClick={autosave.retry}>إعادة محاولة الحفظ</button>}
            {autosave.status === 'conflict' && <><p>توجد نسخة أحدث. اختاري النسخة التي تريدين الاحتفاظ بها قبل متابعة الحفظ.</p><button type="button" onClick={takeServerVersion}>استخدام نسخة الخادم</button><button type="button" onClick={autosave.keepLocalVersion}>الاحتفاظ بتعديلاتي</button></>}
        </aside>}
        <div className="editor-layout">
            <section className="editor-stage" aria-label="معاينة التصميم">
                <div className="editor-canvas-shell">
                    <div className="editor-canvas-host" ref={canvasState.host} />
                    {canvasState.guides.vertical !== null && <span className="editor-guide is-vertical" style={{ left: `${canvasState.guides.vertical / history.value.canvas.width * 100}%` }} aria-hidden="true" />}
                    {canvasState.guides.horizontal !== null && <span className="editor-guide is-horizontal" style={{ top: `${canvasState.guides.horizontal / history.value.canvas.height * 100}%` }} aria-hidden="true" />}
                    {!canvasState.ready && !canvasState.error && <div className="editor-canvas-state" role="status"><span /><p>نجهّز تفاصيل بطاقتك…</p></div>}
                </div>
                {canvasState.error && <p className="editor-error" role="alert">{canvasState.error}</p>}
                {warnings.length > 0 && <ul className="editor-warnings">{warnings.map((warning) => <li key={warning}>{warning}</li>)}</ul>}
                <p className="editor-caption">اختاري عنصراً واسحبيه، أو عدّلي تفاصيله من القائمة.</p>
            </section>
            <aside ref={sidebar} className="editor-sidebar" aria-label="لوحة تحرير البطاقة">{!mobileOpen && controls}</aside>
        </div>
        <EditorMobilePanel open={mobileOpen} onClose={closeMobile}>{controls}</EditorMobilePanel>
        {preview && <InvitationPreview assets={assets} cardParts={preview.parts} scene={preview.document.scene} palette={preview.document.palette} cardUrl={preview.cardUrl} title={subject.title} onClose={() => setPreview(null)} />}
    </main>;
}

export default function Editor(props: EditorProps) {
    const subject = buildSubject(props);
    try {
        if (!subject) throw new Error('Missing editor subject');
        return <EditorWorkspace subject={subject} document={parseDesignDocument(props.document)} revision={props.revision} assets={props.assets} collections={props.collections} recommendedAssetIds={props.recommendedAssetIds} />;
    } catch {
        return <main className="editor-contract-error" dir="rtl"><Head title="تعذّر فتح التصميم" /><img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="ميثاق" /><h1>تعذّر فتح التصميم</h1><p>بيانات البطاقة تحتاج مراجعة قبل بدء التعديل.</p><Link href={subject?.backHref ?? '/app'}>{subject?.backLabel ?? 'الرجوع'}</Link></main>;
    }
}
