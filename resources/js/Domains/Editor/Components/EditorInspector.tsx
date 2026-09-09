import type { EditorAsset } from '../../../Types/Editor';
import type { ImageLayer, Layer } from '../../../Types/Layer';

interface EditorInspectorProps {
    selected: Layer | null;
    replacementAssets: EditorAsset[];
    onChange: (update: (layer: Layer) => Layer) => void;
    onReplace: (asset: EditorAsset) => void;
}

function numeric(value: string, fallback: number): number {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : fallback;
}

export function EditorInspector({ selected, replacementAssets, onChange, onReplace }: EditorInspectorProps) {
    if (!selected) return null;
    const updateFrame = (field: 'x' | 'y' | 'rotation' | 'opacity', value: string): void => onChange((layer) => ({ ...layer, frame: { ...layer.frame, [field]: field === 'opacity' ? Math.min(1, Math.max(0, numeric(value, layer.frame.opacity))) : numeric(value, layer.frame[field]) } }));

    return <aside className="editor-inspector" aria-label="خصائص العنصر المحدد">
        <div className="editor-inspector-heading"><strong>خصائص العنصر</strong><span>{selected.type === 'text' ? 'نص' : selected.type === 'image' ? 'عنصر' : 'شكل'}</span></div>
        <div className="editor-control-grid is-compact">
            <label><span>الموضع س</span><input type="number" value={selected.frame.x} onChange={(event) => updateFrame('x', event.target.value)} /></label>
            <label><span>الموضع ص</span><input type="number" value={selected.frame.y} onChange={(event) => updateFrame('y', event.target.value)} /></label>
            <label><span>الدوران</span><input type="number" min="-360" max="360" value={selected.frame.rotation} onChange={(event) => updateFrame('rotation', event.target.value)} /></label>
            <label><span>الشفافية</span><input type="number" min="0" max="1" step="0.05" value={selected.frame.opacity} onChange={(event) => updateFrame('opacity', event.target.value)} /></label>
        </div>
        {selected.type === 'image' && <div className="editor-replace-control"><label><span>ملاءمة العنصر</span><select value={selected.fit} onChange={(event) => onChange((layer) => layer.type === 'image' ? { ...layer, fit: event.target.value as ImageLayer['fit'] } : layer)}><option value="contain">كامل داخل المساحة</option><option value="cover">ملء المساحة</option></select></label><label><span>استبدال مع حفظ المكان والحجم</span><select value="" onChange={(event) => { const asset = replacementAssets.find((candidate) => candidate.id === event.target.value); if (asset) onReplace(asset); }}><option value="">اختاري عنصراً…</option>{replacementAssets.map((asset) => <option key={asset.id} value={asset.id}>{asset.name}</option>)}</select></label></div>}
    </aside>;
}
