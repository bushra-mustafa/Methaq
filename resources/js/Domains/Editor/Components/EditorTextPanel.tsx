import type { EditorAsset } from '../../../Types/Editor';
import type { TextLayer } from '../../../Types/Layer';
import { PALETTE_ROLES, type PaletteRole } from '../../../Types/Palette';

interface EditorTextPanelProps {
    fonts: EditorAsset[];
    selected: TextLayer | null;
    onAdd: (font: EditorAsset) => void;
    onChange: (update: (layer: TextLayer) => TextLayer) => void;
}

const paletteLabels: Record<PaletteRole, string> = {
    background: 'الخلفية', surface: 'سطح البطاقة', primaryText: 'النص الأساسي', secondaryText: 'النص الثانوي', accent: 'اللون البارز', effect: 'المؤثرات',
};

function boundedNumber(value: string, minimum: number, maximum: number, fallback: number): number {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? Math.min(maximum, Math.max(minimum, parsed)) : fallback;
}

export function EditorTextPanel({ fonts, selected, onAdd, onChange }: EditorTextPanelProps) {
    return <section className="editor-panel-section" aria-labelledby="text-title">
        <header><span>02</span><div><h2 id="text-title">النصوص</h2><p>اكتبي بالعربي أو الإنجليزي واضبطي اتجاه كل جزء.</p></div></header>
        <div className="editor-add-text">
            {fonts.map((font) => <button type="button" key={font.id} style={{ fontFamily: font.fontFamily ?? undefined }} onClick={() => onAdd(font)}>إضافة نص بـ {font.name}</button>)}
        </div>
        {!selected && <p className="editor-empty">اختاري طبقة نص من البطاقة أو أضيفي نصاً جديداً.</p>}
        {selected && <div className="editor-control-stack">
            <label><span>النص</span><textarea rows={4} value={selected.content} dir={selected.direction === 'auto' ? 'auto' : selected.direction} onChange={(event) => onChange((layer) => ({ ...layer, content: event.target.value }))} /></label>
            <div className="editor-control-grid">
                <label><span>اللغة</span><select value={selected.language} onChange={(event) => onChange((layer) => ({ ...layer, language: event.target.value as TextLayer['language'] }))}><option value="ar">عربي</option><option value="en">إنجليزي</option><option value="mixed">عربي وإنجليزي</option></select></label>
                <label><span>الاتجاه</span><select value={selected.direction} onChange={(event) => onChange((layer) => ({ ...layer, direction: event.target.value as TextLayer['direction'] }))}><option value="rtl">من اليمين</option><option value="ltr">من اليسار</option><option value="auto">تلقائي</option></select></label>
                <label><span>المحاذاة</span><select value={selected.alignment} onChange={(event) => onChange((layer) => ({ ...layer, alignment: event.target.value as TextLayer['alignment'] }))}><option value="right">يمين</option><option value="center">وسط</option><option value="left">يسار</option></select></label>
                <label><span>الخط</span><select value={selected.font.assetId} onChange={(event) => { const font = fonts.find((candidate) => candidate.id === event.target.value); if (font) onChange((layer) => ({ ...layer, font: { assetId: font.id, version: font.version } })); }}>{fonts.map((font) => <option key={font.id} value={font.id}>{font.name}</option>)}</select></label>
                <label><span>الحجم</span><input type="number" min="8" max="512" value={selected.fontSize} onChange={(event) => onChange((layer) => ({ ...layer, fontSize: boundedNumber(event.target.value, 8, 512, layer.fontSize) }))} /></label>
                <label><span>السماكة</span><select value={selected.fontWeight} onChange={(event) => onChange((layer) => ({ ...layer, fontWeight: Number(event.target.value) }))}><option value="400">عادي</option><option value="500">متوسط</option><option value="600">بارز</option><option value="700">عريض</option></select></label>
            </div>
            <fieldset className="editor-color-source"><legend>لون النص</legend><label><input type="radio" checked={selected.fill.source === 'palette'} onChange={() => onChange((layer) => ({ ...layer, fill: { source: 'palette', role: 'primaryText' } }))} /> من لوحة الألوان</label><label><input type="radio" checked={selected.fill.source === 'literal'} onChange={() => onChange((layer) => ({ ...layer, fill: { source: 'literal', value: '#074b36' } }))} /> لون خاص</label>
                {selected.fill.source === 'palette' ? <select aria-label="دور اللون" value={selected.fill.role} onChange={(event) => onChange((layer) => ({ ...layer, fill: { source: 'palette', role: event.target.value as PaletteRole } }))}>{PALETTE_ROLES.map((role) => <option key={role} value={role}>{paletteLabels[role]}</option>)}</select> : <input aria-label="اللون الخاص" type="color" value={selected.fill.value} onChange={(event) => onChange((layer) => ({ ...layer, fill: { source: 'literal', value: event.target.value as `#${string}` } }))} />}
            </fieldset>
        </div>}
    </section>;
}
