import { PALETTE_ROLES, type HexColor, type Palette, type PaletteRole } from '../../../Types/Palette';

interface EditorPalettePanelProps {
    palette: Palette;
    onChange: (role: PaletteRole, value: HexColor) => void;
}

const labels: Record<PaletteRole, string> = {
    background: 'الخلفية', surface: 'سطح البطاقة', primaryText: 'النص الأساسي', secondaryText: 'النص الثانوي', accent: 'اللون البارز', effect: 'المؤثرات',
};

export function EditorPalettePanel({ palette, onChange }: EditorPalettePanelProps) {
    return <section className="editor-panel-section" aria-labelledby="palette-title">
        <header><span>04</span><div><h2 id="palette-title">ألوان البطاقة</h2><p>تغيير اللون هنا يحدّث كل العناصر المرتبطة به.</p></div></header>
        <div className="editor-palette-grid">{PALETTE_ROLES.map((role) => <label key={role}><input type="color" value={palette.values[role]} onChange={(event) => onChange(role, event.target.value as HexColor)} /><span>{labels[role]}</span><small dir="ltr">{palette.values[role]}</small></label>)}</div>
    </section>;
}
