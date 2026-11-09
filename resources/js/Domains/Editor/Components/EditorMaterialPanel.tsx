import type { CardMaterial } from '../../../Types/CardMaterial';
import type { HexColor } from '../../../Types/Palette';
import { CARD_MATERIALS, DEFAULT_CARD_MATERIAL, materialTextureUrl } from '../Services/cardMaterials';
import '../../../../css/card-materials.css';

interface Props {
    material?: CardMaterial;
    background: HexColor;
    onChange: (material: CardMaterial) => void;
    onColorChange: (color: HexColor) => void;
}

export function EditorMaterialPanel({ material = DEFAULT_CARD_MATERIAL, background, onChange, onColorChange }: Props) {
    const selected = CARD_MATERIALS.find((option) => option.preset === material.preset) ?? CARD_MATERIALS[0]!;
    const textureUrl = materialTextureUrl(material.preset);

    return <section className="editor-panel-section material-panel" aria-labelledby="material-title">
        <header><div><h2 id="material-title">خامة الكرت</h2><p>اختاري ملمس البطاقة، ثم الدرجة التي تناسب دعوتك.</p></div></header>
        <div className="material-current" aria-label={`معاينة ${selected.name} بلون بطاقتك`}>
            <span className="material-paper" style={{ backgroundColor: background }}>
                {textureUrl && <img src={textureUrl} alt="" style={{ opacity: material.intensity }} />}
            </span>
            <div><strong>{selected.name}</strong><span>بلون بطاقتك الحالي</span></div>
        </div>
        <div className="material-options" role="group" aria-label="اختيار خامة الكرت">
            {CARD_MATERIALS.map((option) => {
                const url = materialTextureUrl(option.preset);
                return <button type="button" key={option.preset} aria-pressed={material.preset === option.preset} onClick={() => onChange({ ...material, preset: option.preset })}>
                    <span className="material-sample" style={{ backgroundColor: option.sampleColor }}>
                        {url && <img src={url} alt="" />}
                        <span aria-hidden="true">م</span>
                        {material.preset === option.preset && <b aria-hidden="true">✓</b>}
                    </span>
                    <strong>{option.name}</strong><small>{option.description}</small>
                </button>;
            })}
        </div>
        <p className="material-hint">تغيير الخامة يحتفظ بلونك ونصوصك وزخارفك.</p>
        {material.preset !== 'plain' && <label className="material-intensity" htmlFor="material-intensity">
            <span>وضوح الملمس <output>{Math.round(material.intensity * 100)}٪</output></span>
            <input id="material-intensity" type="range" min={0} max={1} step={0.05} value={material.intensity} onChange={(event) => onChange({ ...material, intensity: Number(event.target.value) })} />
        </label>}
        <fieldset className="material-colors"><legend>درجات مقترحة للخامة</legend><div>
            {selected.colors.map((color) => <button type="button" key={color.value} aria-pressed={background.toLowerCase() === color.value} onClick={() => onColorChange(color.value)}><span style={{ backgroundColor: color.value }} aria-hidden="true" />{color.name}</button>)}
        </div></fieldset>
        <label className="material-custom-color">لون آخر للكرت<input type="color" aria-label="لون خامة الكرت" value={background} onChange={(event) => onColorChange(event.target.value as HexColor)} /></label>
        <p className="material-hint">الخامة مظهر رقمي يظهر في المعاينة والتصدير. لون النص يتغيّر من أداة النص أو الألوان.</p>
    </section>;
}
