import type { Palette } from '../../../Types/Palette';
import type { EnvelopeAppearance, EnvelopeConfig } from '../../../Types/SceneConfig';
import { DEFAULT_ENVELOPE_APPEARANCE } from '../Services/scenePresentation';
import { InvitationEnvelope } from './InvitationEnvelope';

const styles: Array<{ id: EnvelopeAppearance['style']; name: string; detail: string }> = [
    { id: 'classic', name: 'كلاسيكي', detail: 'طية مثلثة تقليدية' },
    { id: 'luxury', name: 'إطار ذهبي', detail: 'لمعة ناعمة وحواف ذهبية' },
    { id: 'minimal', name: 'بسيط', detail: 'طية قصيرة بخطوط هادئة' },
    { id: 'rounded', name: 'طية مقوّسة', detail: 'غطاء مستدير وناعم' },
    { id: 'gatefold', name: 'بابين', detail: 'يفتح من المنتصف للجانبين' },
];

interface Props { cardUrl?: string; active: boolean; envelope: EnvelopeConfig; palette: Palette; onChange: (style: EnvelopeAppearance['style']) => void }

export function EnvelopeStylePicker({ envelope, palette, onChange, active, cardUrl }: Props) {
    const appearance = envelope.appearance ?? DEFAULT_ENVELOPE_APPEARANCE;
    const selected = styles.find((style) => style.id === appearance.style);
    return <fieldset className="envelope-style-picker">
        <legend>اختاري شكل الظرف</legend>
        {active && <div className="envelope-selected-preview" role="img" aria-label={`معاينة الظرف المختار: ${selected?.name}`}>
            <InvitationEnvelope cardUrl={cardUrl} envelope={envelope} palette={palette} />
            <span>{selected?.name} · بألوان دعوتك</span>
        </div>}
        <div className="envelope-style-grid">
            {styles.map((style) => <label key={style.id} className={`envelope-style-option ${active && style.id === appearance.style ? 'is-selected' : ''}`}>
                <input type="radio" name="envelope-style" value={style.id} checked={active && style.id === appearance.style} onChange={() => onChange(style.id)} />
                <span className="envelope-style-thumbnail" aria-hidden="true"><InvitationEnvelope cardUrl={cardUrl} envelope={{ ...envelope, appearance: { ...appearance, style: style.id } }} palette={palette} /></span>
                <span className="envelope-style-name">{style.name}<span aria-hidden="true">{active && style.id === appearance.style ? '✓' : ''}</span></span>
                <small>{style.detail}</small>
            </label>)}
        </div>
        <p>{active ? 'الأشكال تعرض نفس ألوانك وختمك. اختاري شكلاً ثم «معاينة كضيف» لتجربة فتحه.' : 'اختيار أي نموذج يفعّل فتح الدعوة بظرف.'}</p>
    </fieldset>;
}
