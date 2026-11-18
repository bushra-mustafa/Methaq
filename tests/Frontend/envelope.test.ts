import { test } from 'node:test';
import assert from 'node:assert/strict';
import type { Palette } from '../../resources/js/Types/Palette.ts';
import { ENVELOPE_STYLES, SEAL_STYLES } from '../../resources/js/Types/SceneConfig.ts';
import { applyEnvelopeStyle, createEnvelope, envelopeColors } from '../../resources/js/Domains/Editor/Services/scenePresentation.ts';
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

test('new envelope choices apply editable colours without changing the previous document', () => {
    const original = createEnvelope();
    const snapshot = structuredClone(original);
    const wedding = applyEnvelopeStyle(original, 'wedding-relief');
    const graduation = applyEnvelopeStyle(original, 'graduation');
    assert.equal(wedding.appearance?.style, 'wedding-relief');
    assert.equal(graduation.appearance?.style, 'graduation');
    assert.equal(envelopeColors(wedding, palette).paper, '#eee8dd');
    assert.equal(envelopeColors(graduation, palette).paper, '#1e293b');
    assert.deepEqual(original, snapshot);
    const classic = applyEnvelopeStyle(wedding, 'classic');
    assert.deepEqual(classic.paperColor, wedding.paperColor);
    assert.deepEqual(classic.sealColor, wedding.sealColor);
});
