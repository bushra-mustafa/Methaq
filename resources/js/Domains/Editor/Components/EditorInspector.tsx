import type { EditorAsset } from '../../../Types/Editor';
import type { ImageLayer, Layer } from '../../../Types/Layer';
import { DESIGN_CONTRACT } from '../Services/designContract';
import { NumberField } from './NumberField';

interface EditorInspectorProps {
    selected: Layer | null;
    replacementAssets: EditorAsset[];
    onChange: (update: (layer: Layer) => Layer) => void;
    onReplace: (asset: EditorAsset) => void;
}

export function EditorInspector({ selected, replacementAssets, onChange, onReplace }: EditorInspectorProps) {
    if (!selected) return null;
    const updateFrame = (field: 'x' | 'y' | 'rotation' | 'opacity', value: number): void => {
        onChange((layer) => ({ ...layer, frame: { ...layer.frame, [field]: value } }));
    };
    const edge = DESIGN_CONTRACT.maximumCanvasDimension;

    return <aside className="editor-inspector" aria-label="خصائص العنصر المحدد">
        <div className="editor-inspector-heading"><strong>خصائص العنصر</strong><span>{selected.type === 'text' ? 'نص' : selected.type === 'image' ? 'عنصر' : 'شكل'}</span></div>
        <div className="editor-control-grid is-compact">
            <NumberField label="الموضع س" value={selected.frame.x} minimum={-edge} maximum={edge} onCommit={(value) => updateFrame('x', value)} />
            <NumberField label="الموضع ص" value={selected.frame.y} minimum={-edge} maximum={edge} onCommit={(value) => updateFrame('y', value)} />
            <NumberField label="الدوران" value={selected.frame.rotation} minimum={-360} maximum={360} onCommit={(value) => updateFrame('rotation', value)} />
            <NumberField label="الشفافية" value={selected.frame.opacity} minimum={0} maximum={1} onCommit={(value) => updateFrame('opacity', value)} />
        </div>
        {selected.type === 'image' && <div className="editor-replace-control"><label><span>ملاءمة العنصر</span><select value={selected.fit} onChange={(event) => onChange((layer) => layer.type === 'image' ? { ...layer, fit: event.target.value as ImageLayer['fit'] } : layer)}><option value="contain">كامل داخل المساحة</option><option value="cover">ملء المساحة</option></select></label><label><span>استبدال مع حفظ المكان والحجم</span><select value="" onChange={(event) => { const asset = replacementAssets.find((candidate) => candidate.id === event.target.value); if (asset) onReplace(asset); }}><option value="">اختاري عنصراً…</option>{replacementAssets.map((asset) => <option key={asset.id} value={asset.id}>{asset.name}</option>)}</select></label></div>}
    </aside>;
}
