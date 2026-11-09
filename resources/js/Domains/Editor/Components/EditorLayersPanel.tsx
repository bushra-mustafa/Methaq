import type { EditorAsset } from '../../../Types/Editor';
import type { Layer } from '../../../Types/Layer';

interface EditorLayersPanelProps {
    layers: Layer[];
    assets: EditorAsset[];
    selectedId: string | null;
    onSelect: (id: string) => void;
    onToggleVisibility: (id: string) => void;
}

function layerName(layer: Layer, assets: Map<string, EditorAsset>): string {
    if (layer.type === 'text') return layer.content.trim().slice(0, 28) || 'نص فارغ';
    if (layer.type === 'shape') return layer.shapeKind === 'line' ? 'خط' : 'شكل';
    return assets.get(layer.asset.assetId)?.name ?? 'عنصر تصميم';
}

export function EditorLayersPanel({ layers, assets, selectedId, onSelect, onToggleVisibility }: EditorLayersPanelProps) {
    const assetMap = new Map(assets.map((asset) => [asset.id, asset]));
    return <section className="editor-panel-section" aria-labelledby="layers-title">
        <header><div><h2 id="layers-title">عناصر البطاقة</h2><p>كل نص وزخرفة مستقلان. الأعلى في القائمة يظهر في الأمام.</p></div></header>
        <ol className="editor-layer-list">
            {[...layers].reverse().map((layer) => <li className={selectedId === layer.id ? 'is-selected' : ''} key={layer.id}>
                <button type="button" className="editor-layer-name" aria-pressed={selectedId === layer.id} onClick={() => onSelect(layer.id)}><span aria-hidden="true">{layer.type === 'text' ? 'ت' : '◇'}</span><strong>{layerName(layer, assetMap)}</strong></button>
                <button className="editor-layer-visibility" type="button" title={layer.visible ? 'إخفاء' : 'إظهار'} aria-label={`${layer.visible ? 'إخفاء' : 'إظهار'} ${layerName(layer, assetMap)}`} onClick={() => onToggleVisibility(layer.id)}>{layer.visible ? '◉' : '◌'}</button>
            </li>)}
        </ol>
        {layers.length === 0 && <p className="editor-empty">ابدئي بإضافة نص أو عنصر من المكتبة.</p>}
    </section>;
}
