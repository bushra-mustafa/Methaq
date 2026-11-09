export const CARD_MATERIAL_PRESETS = ['plain', 'cardstock', 'cotton', 'kraft', 'linen', 'wood'] as const;

export type CardMaterialPreset = (typeof CARD_MATERIAL_PRESETS)[number];

export interface CardMaterial {
    preset: CardMaterialPreset;
    version: 1;
    intensity: number;
}
