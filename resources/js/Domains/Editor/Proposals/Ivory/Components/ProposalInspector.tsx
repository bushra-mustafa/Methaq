import { useState } from 'react';
import type { ProposalPart, ProposalPartId } from '../../../../../Types/DesignProposal';
import { PROPOSAL_FONTS } from '../../../../../Types/DesignProposal';
import { PROPOSAL_ASSETS, PROPOSAL_FONT_LABELS, PROPOSAL_LABELS, PROPOSAL_PARENTS } from '../Services/proposalCatalog';
import { readProposalImage } from '../Services/proposalFiles';

interface Props { part: ProposalPart; onChange: (part: ProposalPart) => void; onSelect: (id: ProposalPartId) => void; onReorder: (direction: -1 | 1) => void }
const imageLabels = { architecture: 'القوس المعماري', ornament: 'الزخرفة النباتية', swans: 'البجعتان', chandelier: 'ثريّا', clock: 'ساعة' };

export function ProposalInspector({ part, onChange, onSelect, onReorder }: Props) {
    const [error, setError] = useState('');
    const parent = PROPOSAL_PARENTS[part.id];
    const numeric = (key: 'x' | 'y' | 'width' | 'height' | 'scale' | 'rotation' | 'opacity' | 'gap', label: string, min: number, max: number, step = 1) =>
        <label>{label}<input type="number" value={part[key]} min={min} max={max} step={step} onChange={(event) => { const value = event.target.valueAsNumber; if (Number.isFinite(value)) onChange({ ...part, [key]: Math.min(max, Math.max(min, value)) }); }} /></label>;
    return <section className="ivory-inspector" aria-label="خصائص العنصر">
        <header><small>العنصر المحدد</small><h2>{PROPOSAL_LABELS[part.id]}</h2>{parent && <button type="button" onClick={() => onSelect(parent)}>اختيار مجموعة {PROPOSAL_LABELS[parent]} ←</button>}</header>
        <label className="ivory-check"><input type="checkbox" checked={part.visible} onChange={(event) => onChange({ ...part, visible: event.target.checked })} />إظهار العنصر</label>
        {part.kind === 'text' && <>
            <label>النص<textarea rows={4} maxLength={2000} dir={part.direction} value={part.content} onChange={(event) => onChange({ ...part, content: event.target.value })} /></label>
            <div className="ivory-field-pair"><label>الخط<select value={part.font} onChange={(event) => { const font = PROPOSAL_FONTS.find((font) => font === event.target.value); if (font) onChange({ ...part, font }); }}>{PROPOSAL_FONTS.map((font) => <option key={font} value={font}>{PROPOSAL_FONT_LABELS[font]}</option>)}</select></label>
            <label>حجم الخط<input type="number" min={14} max={120} value={part.fontSize} onChange={(event) => { const value = event.target.valueAsNumber; if (Number.isFinite(value)) onChange({ ...part, fontSize: Math.max(14, Math.min(120, value)) }); }} /></label></div>
            <div className="ivory-field-pair"><label>المحاذاة<select value={part.alignment} onChange={(event) => onChange({ ...part, alignment: event.target.value as 'left' | 'center' | 'right' })}><option value="center">وسط</option><option value="right">يمين</option><option value="left">يسار</option></select></label>
            <label>اتجاه النص<select value={part.direction} onChange={(event) => onChange({ ...part, direction: event.target.value as 'rtl' | 'ltr' | 'auto' })}><option value="rtl">عربي</option><option value="ltr">إنجليزي</option><option value="auto">تلقائي</option></select></label></div>
        </>}
        {(part.kind === 'text' || part.kind === 'shape' || part.kind === 'image' && ['clock', 'chandelier'].includes(part.source)) && <label className="ivory-color">اللون<input type="color" value={part.color} onChange={(event) => onChange({ ...part, color: event.target.value })} /></label>}
        {part.kind === 'image' && <>
            <label>الصورة أو الزخرفة<select value={part.source.startsWith('data:') ? 'custom' : part.source} onChange={(event) => { if (event.target.value !== 'custom') onChange({ ...part, source: event.target.value }); }}>{part.source.startsWith('data:') && <option value="custom">الصورة المرفوعة</option>}{PROPOSAL_ASSETS.map((asset) => <option key={asset} value={asset}>{imageLabels[asset]}</option>)}</select></label>
            <label className="ivory-upload">استبدال بصورة من الجهاز<input type="file" accept="image/png,image/jpeg,image/webp" onChange={(event) => { const file = event.target.files?.[0]; if (!file) return; setError(''); void readProposalImage(file).then((source) => onChange({ ...part, source })).catch((error: unknown) => setError(error instanceof Error ? error.message : 'تعذّرت قراءة الصورة.')); event.target.value = ''; }} /></label>
            <small>PNG، JPG أو WebP · حتى 1 ميغابايت. احتفظي بالشفافية للزخارف.</small>
        </>}
        <div className="ivory-field-pair">{numeric('x', 'إزاحة أفقية', -1800, 1800)}{numeric('y', 'إزاحة عمودية', -5000, 5000)}</div>
        {part.kind !== 'group' && <div className="ivory-field-pair">{numeric('width', part.kind === 'text' ? 'عرض النص' : 'العرض', 20, 1200)}{part.kind !== 'text' && part.id !== 'paper' && numeric('height', 'الارتفاع', 10, 1500)}</div>}
        <div className="ivory-field-pair">{numeric('scale', 'الحجم النسبي', .2, 3, .05)}{numeric('rotation', 'الدوران', -180, 180)}</div>
        <div className="ivory-field-pair">{numeric('opacity', 'الوضوح', 0, 1, .05)}{!['architecture', 'paper', 'ornament', 'envelope-shadow', 'envelope-body', 'envelope-flap', 'envelope-badge'].includes(part.id) && numeric('gap', 'مسافة بعدها', 0, 200)}</div>
        {part.id === 'paper' && <p>ارتفاع الورقة يتكيّف تلقائياً مع محتوى الدعوة.</p>}
        {part.kind === 'group' && <p>الإزاحة والحجم والدوران تُطبّق على المجموعة كاملة. اختاري جزءاً من القائمة لتعديله وحده.</p>}
        <div className="ivory-layer-actions"><button type="button" onClick={() => onReorder(1)}>طبقة للأمام ↑</button><button type="button" onClick={() => onReorder(-1)}>طبقة للخلف ↓</button></div>
        {error && <p role="alert" className="ivory-error">{error}</p>}
    </section>;
}
