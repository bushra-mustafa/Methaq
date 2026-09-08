import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import type { PrototypeDesign, TextSlot, TextPosition } from '../../Types/PrototypeDesign';
import { ReferenceInvitation } from '../../Domains/Editor/Components/ReferenceInvitation';
import { PrototypeCard } from '../../Domains/Editor/Components/PrototypeCard';
import { defaultDesign, parseDesign, STORAGE_KEY } from '../../Domains/Editor/Services/prototypeDesign';
import '../../../css/design-lab.css';
import '../../../css/reference-invitation.css';

const slotLabels: Record<TextSlot, string> = { intro: 'المقدمة', names: 'الأسماء', blessing: 'الدعاء', date: 'الموعد' };

export default function DesignLab() {
    const [design, setDesign] = useState(defaultDesign);
    const [guest, setGuest] = useState(false);
    const [notice, setNotice] = useState('هذه تجربة محلية؛ لا تحفظ بيانات مناسبة حقيقية.');
    function patch(update: Partial<PrototypeDesign>) { setDesign((previous) => ({ ...previous, ...update })); setNotice('تعديلات غير محفوظة'); }
    function move(slot: TextSlot, position: TextPosition) { setDesign((previous) => ({ ...previous, positions: { ...previous.positions, [slot]: position } })); setNotice('تعديلات غير محفوظة'); }
    function save() {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(parseDesign(design))); setNotice('حُفظت النسخة في هذا المتصفح.'); }
        catch { setNotice('تعذّر الحفظ المحلي. قد تكون مساحة المتصفح غير متاحة.'); }
    }
    function restore() {
        try {
            const stored = localStorage.getItem(STORAGE_KEY) ?? localStorage.getItem('methaq:design-lab:v1');
            if (!stored) { setNotice('لا توجد نسخة محفوظة في هذا المتصفح.'); return; }
            if (stored.length > 20000) throw new Error('Oversized document');
            setDesign(parseDesign(JSON.parse(stored) as unknown)); setNotice('تمت استعادة التصميم والألوان والمواضع.');
        } catch { setNotice('تعذّرت الاستعادة؛ النسخة غير صالحة. تصميمك الحالي لم يتغير.'); }
    }
    return <>
        <Head title="تجربة التصميم" />
        <main className="design-lab">
            <header className="lab-header"><Link href="/" aria-label="ميثاق — الرئيسية"><img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="ميثاق — Methaq" width="240" /></Link><span>مختبر التصميم · محلي فقط</span></header>
            <div className="lab-heading"><h1>بودري، وبلمستك.</h1><p>ظرف بختم، بطاقة مقوّسة وزهور، ولمعة تختارين لونها.</p></div>
            <div className="lab-layout">
                <section className="lab-options" aria-label="خيارات التصميم">
                    <h2>تفاصيل البطاقة</h2>
                    <div className="lab-colors">{([['background', 'المشهد'], ['paper', 'ورق البطاقة'], ['envelope', 'الظرف والزهور'], ['ink', 'النص'], ['accent', 'الإطار والزخارف'], ['sparkle', 'اللمعة']] as const).map(([key, label]) => <label key={key}>{label}<input type="color" value={design[key]} onChange={(e) => patch({ [key]: e.target.value })} /></label>)}</div>
                    <label>طريقة الدخول<select value={design.entrance} onChange={(e) => patch({ entrance: e.target.value === 'direct' ? 'direct' : 'envelope' })}><option value="envelope">ظرف وختم</option><option value="direct">البطاقة مباشرة</option></select></label>
                    <label>أحرف الختم<input maxLength={12} value={design.seal} onChange={(e) => patch({ seal: e.target.value })} /></label>
                    <label className="lab-check"><input type="checkbox" checked={design.florals} onChange={(e) => patch({ florals: e.target.checked })} />زخارف الزهور</label>
                    <label>لغة الأسماء<select value={design.language} onChange={(e) => patch({ language: e.target.value === 'ar' ? 'ar' : 'en' })}><option value="en">أسماء إنجليزية وتفاصيل عربية</option><option value="ar">عربي بالكامل</option></select></label>
                    {design.language === 'ar' ? <label>الأسماء بالعربية<input maxLength={60} value={design.arabicNames} onChange={(e) => patch({ arabicNames: e.target.value })} /></label> : <label>الأسماء بالإنجليزية<input dir="ltr" maxLength={60} value={design.englishNames} onChange={(e) => patch({ englishNames: e.target.value })} /></label>}
                    <label>الدعاء<textarea maxLength={160} rows={3} value={design.blessing} onChange={(e) => patch({ blessing: e.target.value })} /></label>
                    <label>عبارة الموعد<input maxLength={80} value={design.dateLabel} onChange={(e) => patch({ dateLabel: e.target.value })} /></label>
                    <label>شدة اللمعة<input type="range" min="0" max="1" step="0.05" value={design.intensity} onChange={(e) => patch({ intensity: Number(e.target.value) })} /></label>
                    <label className="lab-check"><input type="checkbox" checked={design.motion} onChange={(e) => patch({ motion: e.target.checked })} />تحريك اللمعة</label>
                    <div className="lab-actions"><button onClick={save}>حفظ محلي</button><button onClick={restore}>استعادة المحفوظ</button></div>
                    <button className="lab-primary" onClick={() => setGuest(true)}>معاينة الدعوة كاملة</button>
                    <p role="status" className="lab-notice">{notice}</p>
                    <details><summary>تحريك النصوص بالأزرار</summary>{(['intro', 'names', 'blessing', 'date'] as const).map((slot) => <div className="lab-position" key={slot}><span>{slotLabels[slot]}</span><button aria-label={`رفع ${slotLabels[slot]}`} onClick={() => move(slot, { ...design.positions[slot], y: Math.max(0, design.positions[slot].y - 30) })}>↑</button><button aria-label={`خفض ${slotLabels[slot]}`} onClick={() => move(slot, { ...design.positions[slot], y: Math.min(1600, design.positions[slot].y + 30) })}>↓</button></div>)}</details>
                </section>
                <section className="lab-preview" aria-label="معاينة التصميم">
                    <PrototypeCard design={design} onMove={move} exportable />
                    <p className="lab-preview-caption">البطاقة داخل الدعوة · افتحي المعاينة لتجربة الظرف واللمعة</p>
                </section>
            </div>
        </main>
        {guest && <ReferenceInvitation design={design} onClose={() => setGuest(false)} />}
    </>;
}
