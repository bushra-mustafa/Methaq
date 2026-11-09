import type { CardMaterial, CardMaterialPreset } from '../../../Types/CardMaterial.ts';
import type { DesignDocument } from '../../../Types/DesignDocument.ts';
import type { HexColor } from '../../../Types/Palette.ts';

interface MaterialOption {
    preset: CardMaterialPreset;
    name: string;
    description: string;
    sampleColor: HexColor;
    colors: { name: string; value: HexColor }[];
}

export const CARD_MATERIALS: MaterialOption[] = [
    { preset: 'plain', name: 'ورق عادي', description: 'سطح ناعم وبسيط', sampleColor: '#fffaf1', colors: [{ name: 'عاجي', value: '#fffaf1' }, { name: 'أبيض', value: '#ffffff' }, { name: 'بودري', value: '#ead0d0' }] },
    { preset: 'cardstock', name: 'ورق مقوّى', description: 'ملمس مطفي وحافة سميكة', sampleColor: '#e9dfcf', colors: [{ name: 'كريمي', value: '#e9dfcf' }, { name: 'وردي هادئ', value: '#dfbcc0' }, { name: 'عنابي', value: '#531c32' }] },
    { preset: 'cotton', name: 'ورق قطني', description: 'ألياف ناعمة بطابع يدوي', sampleColor: '#f1e9dc', colors: [{ name: 'قطن طبيعي', value: '#f1e9dc' }, { name: 'وردي باهت', value: '#efdcda' }, { name: 'أخضر مريمي', value: '#ccd4c7' }] },
    { preset: 'kraft', name: 'ورق كرافت', description: 'حبيبات وألياف دافئة', sampleColor: '#c5a47a', colors: [{ name: 'كرافت طبيعي', value: '#c5a47a' }, { name: 'رملي', value: '#dfc7a3' }, { name: 'كاكاو', value: '#816347' }] },
    { preset: 'linen', name: 'نسيج كتان', description: 'نسج متقاطع ولمسة قماش', sampleColor: '#dcd6c8', colors: [{ name: 'كتان طبيعي', value: '#dcd6c8' }, { name: 'زيتوني', value: '#8d987e' }, { name: 'أزرق ضبابي', value: '#aebdc9' }] },
    { preset: 'wood', name: 'خشب', description: 'عروق طبيعية ممتدة', sampleColor: '#c99d70', colors: [{ name: 'بلوط فاتح', value: '#d6b88e' }, { name: 'خشب عسلي', value: '#c99d70' }, { name: 'جوز داكن', value: '#80583f' }] },
];

export const DEFAULT_CARD_MATERIAL: CardMaterial = { preset: 'plain', version: 1, intensity: 0.65 };

export function materialTextureUrl(preset: CardMaterialPreset): string | null {
    return preset === 'plain' ? null : `/editor/materials/v1/${preset}.svg`;
}

export function updateCardMaterial(document: DesignDocument, material: CardMaterial): DesignDocument {
    const current = document.canvas.material ?? DEFAULT_CARD_MATERIAL;
    if (current.preset === material.preset && current.version === material.version && current.intensity === material.intensity) return document;
    return { ...document, canvas: { ...document.canvas, material } };
}

export function updateCardBackground(document: DesignDocument, color: HexColor): DesignDocument {
    if (document.canvas.background.source === 'literal' && document.canvas.background.value === color) return document;
    return { ...document, canvas: { ...document.canvas, background: { source: 'literal', value: color } } };
}
