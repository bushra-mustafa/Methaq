import assert from 'node:assert/strict';
import test from 'node:test';
import { audioTracks, chooseTrack, selectedTrackId, withAudio } from '../../resources/js/Domains/Editor/Services/sceneAudio.ts';
import type { DesignDocument } from '../../resources/js/Types/DesignDocument.ts';
import type { EditorAsset } from '../../resources/js/Types/Editor.ts';
import type { AudioConfig } from '../../resources/js/Types/SceneConfig.ts';

function asset(id: string, type: EditorAsset['type'], version = 1): EditorAsset {
    return { id, slug: `asset-${id}`, name: `Asset ${id}`, type, previewUrl: `/asset-${id}`, fontFamily: null, fontUrl: null, version, width: null, height: null, capabilities: {} };
}

const off: AudioConfig = { enabled: false, asset: null, volume: 0.4 };

test('only audio assets are offered as tracks', () => {
    const library = [asset('1', 'decoration'), asset('2', 'audio'), asset('3', 'font'), asset('4', 'audio')];
    assert.deepEqual(audioTracks(library).map((track) => track.id), ['2', '4']);
});

test('choosing a track switches the sound on with that exact version and keeps the volume', () => {
    const next = chooseTrack(off, asset('2', 'audio', 3));
    assert.deepEqual(next, { enabled: true, asset: { assetId: '2', version: 3 }, volume: 0.4 });
    assert.equal(selectedTrackId(next), '2');
});

test('choosing no track switches the sound off but remembers the volume', () => {
    const on = chooseTrack({ ...off, volume: 0.9 }, asset('2', 'audio'));
    assert.deepEqual(chooseTrack(on, null), { enabled: false, asset: null, volume: 0.9 });
    assert.equal(selectedTrackId(chooseTrack(on, null)), null);
});

test('picking what is already chosen returns the same object so no history step is made', () => {
    const track = asset('2', 'audio', 2);
    const on = chooseTrack(off, track);
    assert.equal(chooseTrack(on, track), on);
    assert.equal(chooseTrack(off, null), off);
});

test('a track whose version moved on is chosen again with the new version', () => {
    const on = chooseTrack(off, asset('2', 'audio', 1));
    assert.deepEqual(chooseTrack(on, asset('2', 'audio', 2)).asset, { assetId: '2', version: 2 });
});

test('a switched-off sound reports no selection even when a track is still referenced', () => {
    assert.equal(selectedTrackId({ enabled: false, asset: { assetId: '2', version: 1 }, volume: 0.6 }), null);
});

test('setting the audio replaces only the scene audio and leaves the card untouched', () => {
    const document: DesignDocument = {
        schemaVersion: 1,
        canvas: { schemaVersion: 1, width: 1080, height: 1920, background: { source: 'palette', role: 'background' }, layers: [] },
        palette: { schemaVersion: 1, values: { background: '#f5f2e3', surface: '#ffffff', primaryText: '#074b36', secondaryText: '#315e50', accent: '#b69a50', effect: '#d8c680' } },
        scene: { sceneSchemaVersion: 1, opening: { type: 'direct', durationMs: 0 }, effects: [], audio: off, motionPolicy: 'system' },
    };
    const audio = chooseTrack(off, asset('2', 'audio'));
    const next = withAudio(document, audio);
    assert.equal(next.scene.audio, audio);
    assert.equal(next.canvas, document.canvas);
    assert.equal(next.palette, document.palette);
    assert.equal(document.scene.audio, off);
});
