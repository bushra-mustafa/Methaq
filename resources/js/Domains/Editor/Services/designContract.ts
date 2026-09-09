import type { EffectId, EnvelopePresetId } from '../../../Types/SceneConfig';

export const DESIGN_CONTRACT = {
    schemaVersion: 1,
    canvasWidth: 1080,
    canvasHeight: 1920,
    maximumCanvasDimension: 4096,
    maximumJsonBytes: 1_048_576,
    maximumLayers: 200,
    maximumTextLength: 2000,
    maximumDepth: 12,
    maximumEffects: 2,
    maximumAssetVersion: 65_535,
} as const;

interface EffectCapability {
    version: 1;
    intensity: readonly [number, number];
    speed: readonly [number, number];
}

export const EFFECT_REGISTRY: Record<EffectId, EffectCapability> = {
    sparkle: { version: 1, intensity: [0, 1], speed: [0.25, 2] },
    smoke: { version: 1, intensity: [0, 0.8], speed: [0.25, 1.5] },
};

interface EnvelopeCapability {
    version: 1;
    maximumMonogramLength: number;
}

export const ENVELOPE_REGISTRY: Record<EnvelopePresetId, EnvelopeCapability> = {
    'classic-fold': { version: 1, maximumMonogramLength: 12 },
};
