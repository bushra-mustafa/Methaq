import { Head, Link } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { EditorInspector } from '../../Domains/Editor/Components/EditorInspector';
import { EditorLayersPanel } from '../../Domains/Editor/Components/EditorLayersPanel';
import { EditorLibraryPanel } from '../../Domains/Editor/Components/EditorLibraryPanel';
import { EditorPalettePanel } from '../../Domains/Editor/Components/EditorPalettePanel';
import { EditorTextPanel } from '../../Domains/Editor/Components/EditorTextPanel';
import { useCanvas } from '../../Domains/Editor/Hooks/useCanvas';
import { useHistory } from '../../Domains/Editor/Hooks/useHistory';
import { parseDesignDocument } from '../../Domains/Editor/Services/DesignDocumentSerializer';
import { addAsset, addCollection, addText, layerWarnings, moveLayer, removeLayer, replaceImageAsset, updateLayer, updatePaletteColor } from '../../Domains/Editor/Services/editorDocument';
import type { DesignDocument } from '../../Types/DesignDocument';
import type { EditorAsset, EditorCollection, EditorPanel } from '../../Types/Editor';
import type { EventDTO } from '../../Types/EventDTO';
import type { Layer, TextLayer } from '../../Types/Layer';
import type { HexColor, PaletteRole } from '../../Types/Palette';
import '../../../css/editor.css';

interface EditorProps {
    event: EventDTO;
    document: unknown;
    revision: number;
    assets: EditorAsset[];
    collections: EditorCollection[];
    recommendedAssetIds: string[];
}

const panelLabels: Array<{ value: EditorPanel; label: string; mark: string }> = [
    { value: 'elements', label: 'العناصر', mark: '◇' },
    { value: 'text', label: 'النص', mark: 'ت' },
    { value: 'layers', label: 'الطبقات', mark: '≡' },
    { value: 'colors', label: 'الألوان', mark: '●' },
];

function acceptsShortcut(target: EventTarget | null): boolean {
    return !(target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement || target instanceof HTMLSelectElement || (target instanceof HTMLElement && target.isContentEditable));
}

function EditorWorkspace({ event, document: initialDocument, revision, assets, collections, recommendedAssetIds }: Omit<EditorProps, 'document'> & { document: DesignDocument }) {
    const history = useHistory(initialDocument);
    const [selectedLayerId, setSelectedLayerId] = useState<string | null>(null);
    const [panel, setPanel] = useState<EditorPanel>('elements');
    const selectedLayer = history.value.canvas.layers.find((layer) => layer.id === selectedLayerId) ?? null;
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
        onSelect: setSelectedLayerId,
        onLayerChange: (id, layer) => history.commit((document) => updateLayer(document, id, () => layer)),
    });

    useEffect(() => {
        if (selectedLayerId && !history.value.canvas.layers.some((layer) => layer.id === selectedLayerId)) setSelectedLayerId(null);
    }, [history.value, selectedLayerId]);

    useEffect(() => {
        const onKeyDown = (keyboard: KeyboardEvent): void => {
            if (!acceptsShortcut(keyboard.target)) return;
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
    }, [history, selectedLayer, selectedLayerId]);

    const addVisualAsset = (asset: EditorAsset): void => history.commit((document) => addAsset(document, asset));
    const addAssetCollection = (collection: EditorCollection): void => history.commit((document) => addCollection(document, collection, assetMap));
    const removeSelected = (): void => {
        if (!selectedLayerId) return;
        history.commit((document) => removeLayer(document, selectedLayerId));
        setSelectedLayerId(null);
    };
    const warnings = layerWarnings(history.value, selectedLayer);

    return <main className="editor-page" dir="rtl">
        <Head title={`تصميم ${event.title}`} />
        <header className="editor-topbar">
            <div className="editor-brand"><Link href={`/app/events/${event.id}`} aria-label="الرجوع إلى المناسبة">→</Link><img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="ميثاق" /><div><span>{event.categoryLabel}</span><strong>{event.title}</strong></div></div>
            <div className="editor-history-actions"><span title={`نسخة التصميم ${revision}`}>تعديلات محلية غير محفوظة</span><button type="button" disabled={!history.canUndo} onClick={history.undo}>تراجع</button><button type="button" disabled={!history.canRedo} onClick={history.redo}>إعادة</button></div>
        </header>
        <div className="editor-layout">
            <nav className="editor-panel-nav" aria-label="أدوات التصميم">{panelLabels.map((item) => <button type="button" className={panel === item.value ? 'is-active' : ''} key={item.value} onClick={() => setPanel(item.value)}><span aria-hidden="true">{item.mark}</span>{item.label}</button>)}</nav>
            <aside className="editor-sidebar">
                {panel === 'elements' && <EditorLibraryPanel assets={assets} collections={collections} recommendedAssetIds={recommendedAssetIds} placedAssetIds={placedAssetIds} onAddAsset={addVisualAsset} onAddCollection={addAssetCollection} />}
                {panel === 'text' && <EditorTextPanel fonts={fonts} selected={selectedLayer?.type === 'text' ? selectedLayer : null} onAdd={(font) => history.commit((document) => addText(document, font))} onChange={changeText} />}
                {panel === 'layers' && <EditorLayersPanel layers={history.value.canvas.layers} assets={assets} selectedId={selectedLayerId} onSelect={setSelectedLayerId} onMove={(id, direction) => history.commit((document) => moveLayer(document, id, direction))} onToggleLock={(id) => history.commit((document) => updateLayer(document, id, (layer) => ({ ...layer, locked: !layer.locked })))} onToggleVisibility={(id) => history.commit((document) => updateLayer(document, id, (layer) => ({ ...layer, visible: !layer.visible })))} onRemove={(id) => history.commit((document) => removeLayer(document, id))} />}
                {panel === 'colors' && <EditorPalettePanel palette={history.value.palette} onChange={(role: PaletteRole, color: HexColor) => history.commit((document) => updatePaletteColor(document, role, color))} />}
            </aside>
            <section className="editor-stage" aria-label="معاينة التصميم">
                <div className="editor-stage-heading"><div><small>مقاس القصة</small><strong>1080 × 1920</strong></div><p>اسحبي العنصر أو استخدمي الأسهم لتحريكه. مفتاح Shift يحركه 10 درجات.</p></div>
                <div className="editor-canvas-shell">
                    <div className="editor-canvas-host" ref={canvasState.host} />
                    <span className="editor-safe-area" aria-hidden="true" />
                    {canvasState.guides.vertical !== null && <span className="editor-guide is-vertical" style={{ left: `${canvasState.guides.vertical / history.value.canvas.width * 100}%` }} aria-hidden="true" />}
                    {canvasState.guides.horizontal !== null && <span className="editor-guide is-horizontal" style={{ top: `${canvasState.guides.horizontal / history.value.canvas.height * 100}%` }} aria-hidden="true" />}
                    {!canvasState.ready && !canvasState.error && <div className="editor-canvas-state" role="status"><span /><p>نجهّز البطاقة…</p></div>}
                </div>
                {canvasState.error && <p className="editor-error" role="alert">{canvasState.error}</p>}
                {warnings.length > 0 && <ul className="editor-warnings">{warnings.map((warning) => <li key={warning}>{warning}</li>)}</ul>}
            </section>
            <div className="editor-properties">
                <EditorInspector selected={selectedLayer} replacementAssets={visualAssets} onChange={changeSelected} onReplace={(asset) => { if (selectedLayerId) history.commit((document) => replaceImageAsset(document, selectedLayerId, asset)); }} />
                <aside className="editor-save-note"><span>الحفظ</span><strong>متاح في المرحلة 08</strong><p>التعديلات الحالية تبقى في هذه الصفحة للتجربة. لا تغلقيها قبل الحفظ القادم.</p></aside>
                {selectedLayer && <button className="editor-delete-selected" type="button" onClick={removeSelected}>حذف العنصر المحدد</button>}
            </div>
        </div>
    </main>;
}

export default function Editor(props: EditorProps) {
    try {
        return <EditorWorkspace {...props} document={parseDesignDocument(props.document)} />;
    } catch {
        return <main className="editor-contract-error" dir="rtl"><Head title="تعذّر فتح التصميم" /><img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="ميثاق" /><h1>تعذّر فتح التصميم</h1><p>بيانات البطاقة تحتاج مراجعة قبل بدء التعديل.</p><Link href={`/app/events/${props.event.id}`}>الرجوع إلى المناسبة</Link></main>;
    }
}
