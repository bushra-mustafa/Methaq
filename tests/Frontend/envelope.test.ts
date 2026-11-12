import { test } from 'node:test';
import assert from 'node:assert/strict';
import type { Palette } from '../../resources/js/Types/Palette.ts';
import { ENVELOPE_STYLES, SEAL_STYLES } from '../../resources/js/Types/SceneConfig.ts';
import { createEnvelope, envelopeColors } from '../../resources/js/Domains/Editor/Services/scenePresentation.ts';
import { envelopeThemes } from '../../resources/js/Components/Public/Home/envelopeThemes.ts';

const palette: Palette = {
    schemaVersion: 1,
    values: { background: '#fff8ea', surface: '#fffdf7', primaryText: '#5b1830', secondaryText: '#7f5a4a', accent: '#b48a4a', decorative: '#f2b3c1' },
};

test('envelope colours resolve palette roles and keep literal colours', () => {
    const envelope = { ...createEnvelope(), liningColor: { source: 'palette', role: 'background' } as const, sealColor: { source: 'palette', role: 'accent' } as const };

    assert.deepEqual(envelopeColors(envelope, palette), { paper: '#591b32', lining: '#fff8ea', seal: '#b48a4a' });
});

test('the home showcase only shows envelope and seal shapes owners can pick in the editor', () => {
    for (const theme of envelopeThemes) {
        assert.ok(ENVELOPE_STYLES.includes(theme.appearance.style), `${theme.id} envelope style`);
        assert.ok(SEAL_STYLES.includes(theme.appearance.sealStyle) && theme.appearance.sealStyle !== 'none', `${theme.id} seal style`);
    }
});
