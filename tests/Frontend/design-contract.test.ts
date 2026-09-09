import { test } from 'node:test';
import assert from 'node:assert/strict';
import type { DesignDocument } from '../../resources/js/Types/DesignDocument.ts';
import {
    parseDesignDocument,
    parseDesignDocumentJson,
    parseDesignSnapshot,
    parseSaveDesignPayload,
    serializeDesignDocument,
} from '../../resources/js/Domains/Editor/Services/DesignDocumentSerializer.ts';
import {
    fromFabricDocument,
    toFabricDocument,
} from '../../resources/js/Domains/Editor/Services/FabricAdapter.ts';
import { DESIGN_CONTRACT } from '../../resources/js/Domains/Editor/Services/designContract.ts';

function designFixture(): DesignDocument {
    return {
        schemaVersion: 1,
        canvas: {
            schemaVersion: 1,
            width: 1080,
            height: 1920,
            background: { source: 'palette', role: 'background' },
            layers: [
                {
                    id: 'names_1',
                    type: 'text',
                    frame: { x: 120, y: 600, width: 840, height: 420, scaleX: 1, scaleY: 1, rotation: 0, opacity: 1 },
                    locked: false,
                    visible: true,
                    content: 'ليان و آدم',
                    language: 'ar',
                    direction: 'rtl',
                    alignment: 'center',
                    font: { assetId: '4', version: 1 },
                    fontSize: 144,
                    fontWeight: 400,
                    fill: { source: 'palette', role: 'primaryText' },
                },
                {
                    id: 'flowers_1',
                    type: 'image',
                    frame: { x: 40, y: 80, width: 320, height: 420, scaleX: 1, scaleY: 1, rotation: 0, opacity: 0.9 },
                    locked: true,
                    visible: true,
                    asset: { assetId: '8', version: 2 },
                    fit: 'contain',
                },
                {
                    id: 'divider_1',
                    type: 'shape',
                    frame: { x: 390, y: 1320, width: 300, height: 8, scaleX: 1, scaleY: 1, rotation: 0, opacity: 1 },
                    locked: false,
                    visible: true,
                    shapeKind: 'line',
                    fill: null,
                    stroke: { source: 'palette', role: 'accent' },
                    strokeWidth: 3,
                },
            ],
        },
        palette: {
            schemaVersion: 1,
            values: {
                background: '#ead0d0',
                surface: '#fffaf1',
                primaryText: '#866143',
                secondaryText: '#6f5949',
                accent: '#a78550',
                effect: '#fff5da',
            },
        },
        scene: {
            sceneSchemaVersion: 1,
            opening: {
                type: 'envelope',
                durationMs: 1700,
                envelope: {
                    presetId: 'classic-fold',
                    presetVersion: 1,
                    paperColor: { source: 'literal', value: '#d8b0b0' },
                    liningColor: { source: 'palette', role: 'surface' },
                    sealColor: { source: 'palette', role: 'accent' },
                    monogram: 'L & A',
                },
            },
            effects: [
                { effectId: 'sparkle', effectVersion: 1, enabled: true, color: { source: 'palette', role: 'effect' }, intensity: 0.55, speed: 1 },
                { effectId: 'smoke', effectVersion: 1, enabled: false, color: { source: 'literal', value: '#ffffff' }, intensity: 0.3, speed: 0.75 },
            ],
            audio: { enabled: false, asset: null, volume: 0.6 },
            motionPolicy: 'system',
        },
    };
}

test('design document survives JSON and Fabric adapter round trips', () => {
    const document = designFixture();
    const decoded = parseDesignDocumentJson(serializeDesignDocument(document));
    assert.deepEqual(decoded, document);
    assert.deepEqual(fromFabricDocument(toFabricDocument(document), document), document);
});

test('normalization strips unknown executable and entitlement fields', () => {
    const normalized = parseDesignDocument({ ...designFixture(), script: 'alert(1)', is_paid: true });
    assert.equal('script' in normalized, false);
    assert.equal('is_paid' in normalized, false);
});

test('external asset references and unsupported registry versions are rejected', () => {
    const document = designFixture();
    const textLayer = document.canvas.layers[0];
    assert.ok(textLayer?.type === 'text');
    assert.throws(() => parseDesignDocument({
        ...document,
        canvas: { ...document.canvas, layers: [{ ...textLayer, font: { assetId: 'https://example.com/font.woff2', version: 1 } }] },
    }));
    assert.throws(() => parseDesignDocument({
        ...document,
        scene: { ...document.scene, effects: [{ ...document.scene.effects[0], effectVersion: 2 }] },
    }));
});

test('contract limits reject duplicates, excessive depth and oversized JSON', () => {
    const document = designFixture();
    assert.throws(() => parseDesignDocument({
        ...document,
        canvas: { ...document.canvas, layers: [document.canvas.layers[0], document.canvas.layers[0]] },
    }));

    let nested: unknown = 'leaf';
    for (let index = 0; index <= DESIGN_CONTRACT.maximumDepth; index += 1) nested = { nested };
    assert.throws(() => parseDesignDocument({ ...document, extra: nested }));
    assert.throws(() => parseDesignDocumentJson(' '.repeat(DESIGN_CONTRACT.maximumJsonBytes + 1)));
});

test('save and snapshot revisions are positive server counters', () => {
    const document = designFixture();
    assert.deepEqual(parseSaveDesignPayload({ document, expectedRevision: 7 }), { document, expectedRevision: 7 });
    assert.deepEqual(parseDesignSnapshot({ document, revision: 8 }), { document, revision: 8 });
    assert.throws(() => parseSaveDesignPayload({ document, expectedRevision: 0 }));
    assert.throws(() => parseDesignSnapshot({ document, revision: -1 }));
});

test('envelope appearance survives saving without changing legacy scenes', () => {
    const document = designFixture();
    assert.ok(document.scene.opening.type === 'envelope');
    const appearance = { style: 'luxury', sealStyle: 'medallion', sealX: 65, sealY: 60, sealSize: 24 } as const;
    document.scene.opening.envelope.appearance = appearance;
    const decoded = parseDesignDocumentJson(serializeDesignDocument(document));
    assert.deepEqual(decoded, document);
    for (const style of ['classic', 'luxury', 'minimal', 'rounded', 'gatefold'] as const) {
        document.scene.opening.envelope.appearance = { ...appearance, style };
        assert.deepEqual(parseDesignDocumentJson(serializeDesignDocument(document)), document);
    }
    for (const invalid of [{ sealX: 19 }, { sealY: 76 }, { sealSize: 30 }, { sealX: 50.5 }, { style: 'custom-html' }, { sealStyle: 'script' }]) {
        assert.throws(() => parseDesignDocument({ ...document, scene: { ...document.scene, opening: { ...document.scene.opening, envelope: { ...document.scene.opening.envelope, appearance: { ...appearance, ...invalid } } } } }));
    }
});

test('optional invitation cover roundtrips and rejects malformed presentation fields', () => {
    const document = designFixture();
    const cover = { heading: 'أهلاً', names: 'Lina & Ali', dateLabel: '10 · 10', message: 'بحضوركم', language: 'mixed', decoration: 'floral', animateText: true } as const;
    document.scene.cover = cover;
    assert.deepEqual(parseDesignDocument(document).scene.cover, cover);
    for (const invalid of [{ names: 'a'.repeat(161) }, { heading: '<script>x</script>' }, { decoration: 'custom' }, { animateText: 'true' }]) {
        assert.throws(() => parseDesignDocument({ ...document, scene: { ...document.scene, cover: { ...cover, ...invalid } } }));
    }
});

test('optional scene backdrop is independent from the card palette and has a closed preset list', () => {
    const document = designFixture();
    document.scene.backdrop = { preset: 'burgundy-nebula' };
    assert.deepEqual(parseDesignDocument(document).scene.backdrop, { preset: 'burgundy-nebula' });
    assert.throws(() => parseDesignDocument({ ...document, scene: { ...document.scene, backdrop: { preset: 'remote-image' } } }));
});
