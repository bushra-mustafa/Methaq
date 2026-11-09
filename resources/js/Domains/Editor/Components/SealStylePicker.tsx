import { useId } from 'react';
import type { CSSProperties } from 'react';
import type { EnvelopeAppearance } from '../../../Types/SceneConfig';
import type { HexColor } from '../../../Types/Palette';
import { InvitationSeal } from './InvitationSeal';

const styles: { id: EnvelopeAppearance['sealStyle']; name: string }[] = [
    { id: 'wax', name: 'شمع كلاسيكي' },
    { id: 'wax-oval', name: 'شمع بيضاوي' },
    { id: 'wax-heart', name: 'شمع قلب' },
    { id: 'wax-square', name: 'شمع مربّع' },
    { id: 'wax-flower', name: 'شمع زهرة' },
    { id: 'medallion', name: 'ميدالية معدنية' },
    { id: 'medallion-octagon', name: 'معدني مثمّن' },
    { id: 'embossed', name: 'طباعة بارزة' },
    { id: 'methaq', name: 'دائري ناعم' },
    { id: 'none', name: 'بدون ختم' },
];

interface Props {
    value: EnvelopeAppearance['sealStyle'];
    color: HexColor;
    onChange: (style: EnvelopeAppearance['sealStyle']) => void;
}

export function SealStylePicker({ value, color, onChange }: Props) {
    const name = useId();
    return <fieldset className="seal-style-picker" style={{ '--seal-color': color } as CSSProperties}>
        <legend>اختاري نوع الختم وشكله</legend>
        <div className="seal-style-grid">{styles.map((style) => <label className={style.id === value ? 'is-selected' : ''} key={style.id}>
            <input type="radio" name={name} value={style.id} checked={value === style.id} onChange={() => onChange(style.id)} />
            <span className="seal-style-sample" aria-hidden="true">{style.id === 'none' ? <i>—</i> : <InvitationSeal style={style.id} />}</span>
            <strong>{style.name}</strong>
            {style.id === value && <b aria-hidden="true">✓</b>}
        </label>)}</div>
        <p>شعار ميثاق ثابت داخل كل الأشكال. اختاري الشكل واللون اللي يناسب دعوتك.</p>
    </fieldset>;
}
