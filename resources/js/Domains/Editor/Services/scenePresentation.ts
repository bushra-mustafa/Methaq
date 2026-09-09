import type { EnvelopeAppearance, EnvelopeConfig, OpeningConfig } from '../../../Types/SceneConfig';

export const DEFAULT_ENVELOPE_APPEARANCE: EnvelopeAppearance = {
    style: 'classic', sealStyle: 'wax', sealX: 50, sealY: 52, sealSize: 20,
};

export function createEnvelope(): EnvelopeConfig {
    return {
        presetId: 'classic-fold', presetVersion: 1,
        paperColor: { source: 'literal', value: '#591b32' },
        liningColor: { source: 'literal', value: '#ead0d0' },
        sealColor: { source: 'literal', value: '#b69a50' },
        monogram: 'م', appearance: { ...DEFAULT_ENVELOPE_APPEARANCE },
    };
}

export function changeOpeningType(current: OpeningConfig, type: OpeningConfig['type']): OpeningConfig {
    if (current.type === type) return current;
    if (type === 'direct') return { type, durationMs: 0 };
    const durationMs = current.type === 'direct' ? 1800 : current.durationMs;
    if (type === 'fade') return { type, durationMs };
    return { type, durationMs, envelope: createEnvelope() };
}
