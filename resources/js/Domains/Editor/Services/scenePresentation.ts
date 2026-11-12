import type { HexColor, Palette } from '../../../Types/Palette.ts';
import type { EnvelopeAppearance, EnvelopeConfig, OpeningConfig } from '../../../Types/SceneConfig.ts';
import { resolveColor } from './editorDocument.ts';

export const DEFAULT_ENVELOPE_APPEARANCE: EnvelopeAppearance = {
    style: 'classic', sealStyle: 'wax', sealX: 50, sealY: 52, sealSize: 20,
};

export interface EnvelopeColors {
    paper: HexColor;
    lining: HexColor;
    seal: HexColor;
}

/** Resolves an envelope's palette references into the colours InvitationEnvelope paints. */
export function envelopeColors(envelope: EnvelopeConfig, palette: Palette): EnvelopeColors {
    const document = { palette };

    return {
        paper: resolveColor(envelope.paperColor, document),
        lining: resolveColor(envelope.liningColor, document),
        seal: resolveColor(envelope.sealColor, document),
    };
}

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
