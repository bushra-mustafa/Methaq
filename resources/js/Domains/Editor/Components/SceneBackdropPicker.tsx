import type { SceneBackdrop, SceneBackdropPreset } from '../../../Types/SceneConfig';

interface BackdropOption { preset: SceneBackdropPreset; name: string; detail: string }

const OPTIONS: BackdropOption[] = [
    { preset: 'inherit', name: 'من ألوان البطاقة', detail: 'خلفية هادئة مرتبطة بلوحة البطاقة' },
    { preset: 'burgundy-nebula', name: 'وهج عنابي', detail: 'سحب ولمعة خلف بطاقة فاتحة' },
    { preset: 'blush-cloud', name: 'سحاب بودري', detail: 'وردي ناعم لحفلات هادئة' },
    { preset: 'midnight-gold', name: 'ليل ذهبي', detail: 'كحلي عميق بنجوم ذهبية' },
    { preset: 'emerald-silk', name: 'حرير زمردي', detail: 'أخضر داكن بلمعة ناعمة' },
];

interface Props { backdrop?: SceneBackdrop; onChange: (backdrop: SceneBackdrop) => void }

export function SceneBackdropPicker({ backdrop, onChange }: Props) {
    const selected = backdrop?.preset ?? 'inherit';
    return <fieldset className="scene-backdrop-picker"><legend>خلفية حول البطاقة</legend>
        <p>تظهر خارج البطاقة فقط، ولا تغيّر لون أو عناصر البطاقة نفسها.</p>
        <div role="radiogroup" aria-label="اختيار خلفية المشهد">
            {OPTIONS.map((option) => <label key={option.preset} className={selected === option.preset ? 'is-selected' : ''}>
                <input type="radio" name="scene-backdrop" value={option.preset} checked={selected === option.preset} onChange={() => onChange({ preset: option.preset })} />
                <span className={`scene-backdrop-swatch is-${option.preset}`} aria-hidden="true" />
                <span><strong>{option.name}</strong><small>{option.detail}</small></span>
            </label>)}
        </div>
    </fieldset>;
}
