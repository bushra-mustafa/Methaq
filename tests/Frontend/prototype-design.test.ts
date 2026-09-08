import { test } from 'node:test';
import assert from 'node:assert/strict';
import { defaultDesign, parseDesign } from '../../resources/js/Domains/Editor/Services/prototypeDesign.ts';

test('design round trip preserves mixed text, colors, motion and positions', () => {
    const design = defaultDesign();
    design.positions.names.y = 800;
    design.sparkle = '#112233';
    design.motion = false;
    assert.deepEqual(parseDesign(JSON.parse(JSON.stringify(design))), design);
});
test('malformed documents cannot replace current design', () => {
    for (const value of [null, [], {}, { ...defaultDesign(), schemaVersion: 99 }, { ...defaultDesign(), background: 'url(https://example.com)' }, { ...defaultDesign(), intensity: Infinity }, { ...defaultDesign(), englishNames: 'x'.repeat(61) }, { ...defaultDesign(), positions: { names: { x: -1, y: 0 } } }]) {
        assert.throws(() => parseDesign(value));
    }
});
test('unknown properties and executable content are not retained', () => {
    const design = parseDesign({ ...defaultDesign(), script: 'unexpected', is_paid: true });
    assert.equal('script' in design, false);
    assert.equal('is_paid' in design, false);
});

test('legacy documents migrate without losing text or positions', () => {
    const legacy = { ...defaultDesign(), schemaVersion: 1, paper: undefined, accent: undefined, seal: undefined };
    const restored = parseDesign(legacy);
    assert.equal(restored.schemaVersion, 2);
    assert.equal(restored.paper, defaultDesign().paper);
    assert.deepEqual(restored.positions, legacy.positions);
    assert.equal(restored.englishNames, legacy.englishNames);
});
test('scene settings validate and survive save and restore', () => {
    const design = { ...defaultDesign(), entrance: 'direct', seal: 'ل و آ', florals: false };
    assert.deepEqual(parseDesign(JSON.parse(JSON.stringify(design))), design);
    for (const update of [{ seal: 'x'.repeat(13) }, { entrance: 'script' }, { florals: 'true' }, { paper: '<svg>' }]) {
        assert.throws(() => parseDesign({ ...defaultDesign(), ...update }));
    }
});
