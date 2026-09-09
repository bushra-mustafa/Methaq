import assert from 'node:assert/strict';
import { test } from 'node:test';
import { addCollection, layerWarnings, replaceImageAsset, resolveColor, updatePaletteColor } from '../../resources/js/Domains/Editor/Services/editorDocument.ts';
import { commitHistory, createHistory, redoHistory, undoHistory } from '../../resources/js/Domains/Editor/Services/editorHistory.ts';
import type { DesignDocument } from '../../resources/js/Types/DesignDocument.ts';
import type { EditorAsset, EditorCollection } from '../../resources/js/Types/Editor.ts';

function documentFixture(): DesignDocument {
    return {
        schemaVersion: 1,
        canvas: { schemaVersion: 1, width: 1080, height: 1920, background: { source: 'palette', role: 'background' }, layers: [] },
        palette: { schemaVersion: 1, values: { background: '#f5f2e3', surface: '#ffffff', primaryText: '#074b36', secondaryText: '#315e50', accent: '#b69a50', effect: '#d8c680' } },
        scene: { sceneSchemaVersion: 1, opening: { type: 'direct', durationMs: 0 }, effects: [], audio: { enabled: false, asset: null, volume: 0.6 }, motionPolicy: 'system' },
    };
}

function asset(id: string, name: string): EditorAsset {
    return { id, slug: `asset-${id}`, name, type: 'decoration', previewUrl: `/asset-${id}.png`, fontFamily: null, fontUrl: null, version: 1, width: 300, height: 400, capabilities: {} };
}

test('adding a collection is one undoable history operation', () => {
    const first = asset('1', 'غصن أول');
    const second = asset('2', 'غصن ثانٍ');
    const collection: EditorCollection = {
        id: '10', slug: 'pair', name: 'غصنان', thumbnailUrl: null,
        items: [first, second].map((item, index) => ({ assetId: item.id, sortOrder: index, placement: { x: index * 200, y: index * 300, width: 300, height: 400, scaleX: 1, scaleY: 1, rotation: 0, opacity: 1, locked: false } })),
    };
    const assets = new Map([[first.id, first], [second.id, second]]);
    const added = commitHistory(createHistory(documentFixture()), (document) => addCollection(document, collection, assets));

    assert.equal(added.past.length, 1);
    assert.equal(added.present.canvas.layers.length, 2);
    const duplicate = commitHistory(added, (document) => addCollection(document, collection, assets));
    assert.equal(duplicate, added);
    assert.equal(duplicate.present.canvas.layers.length, 2);
    const undone = undoHistory(added);
    assert.equal(undone.present.canvas.layers.length, 0);
    assert.equal(redoHistory(undone).present.canvas.layers.length, 2);
});

test('replacing an image keeps its placement, visibility and lock state', () => {
    const original = asset('1', 'قديم');
    const replacement = asset('2', 'جديد');
    const document = documentFixture();
    document.canvas.layers.push({ id: 'image_1', type: 'image', frame: { x: 91, y: 144, width: 410, height: 520, scaleX: 1.2, scaleY: 0.8, rotation: 17, opacity: 0.7 }, locked: true, visible: false, asset: { assetId: original.id, version: original.version }, fit: 'cover' });

    const result = replaceImageAsset(document, 'image_1', replacement);
    const changed = result.canvas.layers[0];
    assert.ok(changed?.type === 'image');
    assert.deepEqual(changed.frame, document.canvas.layers[0]?.frame);
    assert.equal(changed.locked, true);
    assert.equal(changed.visible, false);
    assert.equal(changed.fit, 'cover');
    assert.deepEqual(changed.asset, { assetId: '2', version: 1 });
});

test('palette changes affect linked colors while literal colors remain fixed', () => {
    const document = documentFixture();
    const changed = updatePaletteColor(document, 'primaryText', '#112233');
    assert.equal(resolveColor({ source: 'palette', role: 'primaryText' }, changed), '#112233');
    assert.equal(resolveColor({ source: 'literal', value: '#abcdef' }, changed), '#abcdef');
});

test('editor warns about cropped and unreadable text', () => {
    const document = documentFixture();
    const textLayer = { id: 'warning_text', type: 'text' as const, frame: { x: -20, y: 10, width: 120, height: 30, scaleX: 1, scaleY: 1, rotation: 0, opacity: 1 }, locked: false, visible: true, content: 'نص طويل جداً لا يناسب هذه المساحة', language: 'ar' as const, direction: 'rtl' as const, alignment: 'center' as const, font: { assetId: '1', version: 1 }, fontSize: 48, fontWeight: 400, fill: { source: 'literal' as const, value: '#f5f2e3' as const } };
    document.canvas.layers.push(textLayer);

    const warnings = layerWarnings(document, textLayer);
    assert.equal(warnings.length, 3);
});
