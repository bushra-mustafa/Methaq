import type { InvitationCover } from '../../../Types/SceneConfig';

export const DEFAULT_COVER: InvitationCover = { heading: 'فرحتنا تحلى بوجودكم', names: '', dateLabel: '', message: 'بحضوركم تكتمل فرحتنا', language: 'mixed', decoration: 'floral', animateText: true };
export function SceneCoverPanel({ cover, onChange }: { cover?: InvitationCover; onChange: (cover: InvitationCover) => void }) {
    const value = cover ?? DEFAULT_COVER;
    const update = (changes: Partial<InvitationCover>): void => onChange({ ...value, ...changes });
    return <fieldset className="scene-effects-panel"><legend>كتابة وزخارف الغلاف</legend><div>
        {!cover && <button type="button" className="scene-secondary-button" onClick={() => onChange(DEFAULT_COVER)}>تفعيل الغلاف المستقل وحركة النصوص</button>}
        <label>عنوان الترحيب<input maxLength={120} value={value.heading} dir="auto" onChange={(event) => update({ heading: event.target.value })} /></label>
        <label>الأسماء على الورقة<input maxLength={160} value={value.names} dir="auto" placeholder="اكتبي الأسماء كما تريدين ظهورها" onChange={(event) => update({ names: event.target.value })} /></label>
        <label>التاريخ المعروض<input maxLength={80} value={value.dateLabel} dir="auto" placeholder="نص للعرض فقط، لا يغير موعد المناسبة" onChange={(event) => update({ dateLabel: event.target.value })} /></label>
        <label>العبارة أو الدعاء<textarea maxLength={500} value={value.message} dir="auto" onChange={(event) => update({ message: event.target.value })} /></label>
        <label>لغة الأسماء<select value={value.language} onChange={(event) => update({ language: event.target.value as InvitationCover['language'] })}><option value="ar">عربي · أميري</option><option value="en">إنجليزي · Pinyon</option><option value="mixed">مختلط · حسب النص</option></select></label>
        <label>زخرفة الغلاف<select value={value.decoration} onChange={(event) => update({ decoration: event.target.value as InvitationCover['decoration'] })}><option value="floral">أغصان وزهور</option><option value="halo">هالة ناعمة</option><option value="none">بدون زخرفة</option></select></label>
        <label className="scene-effect-toggle"><input type="checkbox" checked={cover?.animateText ?? false} onChange={(event) => update({ animateText: event.target.checked })} />ظهور نصوص البطاقة بالتتابع</label>
    </div></fieldset>;
}
