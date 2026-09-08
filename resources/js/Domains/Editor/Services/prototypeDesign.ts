import type { PrototypeDesign, TextSlot } from '../../../Types/PrototypeDesign';

export const STORAGE_KEY = 'methaq:design-lab:v2';
export const TEXT_SLOTS: TextSlot[] = ['intro', 'names', 'blessing', 'date'];
export function defaultDesign(): PrototypeDesign {
    return {
        schemaVersion: 2, background: '#ead0d0', paper: '#fffaf1', accent: '#a78550', envelope: '#d8b0b0', seal: 'L & A', entrance: 'envelope', florals: true, ink: '#866143', sparkle: '#fff5da',
        intensity: 0.55, motion: true, arabicNames: 'ليان و آدم', englishNames: 'Layan & Adam',
        language: 'en', blessing: 'اللهم بارك لنا وبارك علينا\nواجمع بيننا في خير',
        dateLabel: 'موعد فرحتنا · ١٨ سبتمبر',
        positions: { intro: { x: 120, y: 560 }, names: { x: 120, y: 675 }, blessing: { x: 120, y: 320 }, date: { x: 120, y: 1370 } },
    };
}
function record(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}
export function parseDesign(value: unknown): PrototypeDesign {
    if (!record(value) || (value.schemaVersion !== 1 && value.schemaVersion !== 2)) throw new Error('نسخة الحفظ غير مدعومة.');
    const color = (key: string): string => {
        const v = value[key];
        if (typeof v !== 'string' || !/^#[0-9a-f]{6}$/i.test(v)) throw new Error('لون غير صالح.');
        return v;
    };
    const text = (key: string, max: number): string => {
        const v = value[key];
        if (typeof v !== 'string' || v.length > max) throw new Error('النص المحفوظ غير صالح.');
        return v;
    };
    if (value.language !== 'ar' && value.language !== 'en') throw new Error('لغة غير صالحة.');
    if (typeof value.motion !== 'boolean' || typeof value.intensity !== 'number' || !Number.isFinite(value.intensity) || value.intensity < 0 || value.intensity > 1) throw new Error('إعداد المؤثر غير صالح.');
    const rawPositions = value.positions;
    if (!record(rawPositions)) throw new Error('مواضع غير صالحة.');
    const positions = defaultDesign().positions;
    for (const key of TEXT_SLOTS) {
        const p = rawPositions[key];
        if (!record(p) || typeof p.x !== 'number' || typeof p.y !== 'number' || !Number.isFinite(p.x) || !Number.isFinite(p.y) || p.x < 0 || p.x > 240 || p.y < 0 || p.y > 1600) throw new Error('موضع النص خارج الحدود.');
        positions[key] = { x: p.x, y: p.y };
    }
    const defaults = defaultDesign();
    if (value.schemaVersion === 2 && (typeof value.florals !== 'boolean' || (value.entrance !== 'envelope' && value.entrance !== 'direct'))) throw new Error('إعداد المشهد غير صالح.');
    return { schemaVersion: 2,
        paper: value.schemaVersion === 1 ? defaults.paper : color('paper'),
        accent: value.schemaVersion === 1 ? defaults.accent : color('accent'),
        envelope: value.schemaVersion === 1 ? defaults.envelope : color('envelope'),
        seal: value.schemaVersion === 1 ? defaults.seal : text('seal', 12),
        entrance: value.entrance === 'direct' ? 'direct' : 'envelope',
        florals: value.schemaVersion === 1 ? true : value.florals === true,
        background: color('background'), ink: color('ink'), sparkle: color('sparkle'), intensity: value.intensity, motion: value.motion, language: value.language,
        arabicNames: text('arabicNames', 60), englishNames: text('englishNames', 60), blessing: text('blessing', 160), dateLabel: text('dateLabel', 80), positions };
}
