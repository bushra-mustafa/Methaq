import type { EditorAsset } from '../../../Types/Editor';
import type { Layer } from '../../../Types/Layer';

interface EditorLayersPanelProps {
    layers: Layer[];
    assets: EditorAsset[];
    selectedId: string | null;
    onSelect: (id: string) => void;
    onMove: (id: string, direction: 'forward' | 'backward') => void;
    onToggleLock: (id: string) => void;
    onToggleVisibility: (id: string) => void;
    onRemove: (id: string) => void;
}

function layerName(layer: Layer, assets: Map<string, EditorAsset>): string {
    if (layer.type === 'text') return layer.content.trim().slice(0, 28) || 'نص فارغ';
    if (layer.type === 'shape') return layer.shapeKind === 'line' ? 'خط' : 'شكل';
    return assets.get(layer.asset.assetId)?.name ?? 'عنصر تصميم';
}

export function EditorLayersPanel({ layers, assets, selectedId, onSelect, onMove, onToggleLock, onToggleVisibility, onRemove }: EditorLayersPanelProps) {
    const assetMap = new Map(assets.map((asset) => [asset.id, asset]));
    return <section className="editor-panel-section" aria-labelledby="layers-title">
        <header><span>03</span><div><h2 id="layers-title">الطبقات</h2><p>الأعلى في القائمة يظهر فوق بقية العناصر.</p></div></header>
        <ol className="editor-layer-list">
            {[...layers].reverse().map((layer) => <li className={selectedId === layer.id ? 'is-selected' : ''} key={layer.id}>
                <button type="button" className="editor-layer-name" onClick={() => onSelect(layer.id)}><small>{layer.type === 'text' ? 'نص' : layer.type === 'image' ? 'عنصر' : 'شكل'}</small><strong>{layerName(layer, assetMap)}</strong></button>
                <div className="editor-layer-actions">
                    <button type="button" title="تحريك للأعلى" aria-label="تحريك الطبقة للأعلى" onClick={() => onMove(layer.id, 'forward')}>↑</button>
                    <button type="button" title="تحريك للأسفل" aria-label="تحريك الطبقة للأسفل" onClick={() => onMove(layer.id, 'backward')}>↓</button>
                    <button type="button" title={layer.locked ? 'فك القفل' : 'قفل'} aria-label={layer.locked ? 'فك قفل الطبقة' : 'قفل الطبقة'} onClick={() => onToggleLock(layer.id)}>{layer.locked ? '●' : '○'}</button>
                    <button type="button" title={layer.visible ? 'إخفاء' : 'إظهار'} aria-label={layer.visible ? 'إخفاء الطبقة' : 'إظهار الطبقة'} onClick={() => onToggleVisibility(layer.id)}>{layer.visible ? '◉' : '◌'}</button>
                    <button type="button" className="is-danger" title="حذف" aria-label="حذف الطبقة" onClick={() => onRemove(layer.id)}>×</button>
                </div>
            </li>)}
        </ol>
        {layers.length === 0 && <p className="editor-empty">ابدئي بإضافة نص أو عنصر من المكتبة.</p>}
    </section>;
}
