import assert from 'node:assert/strict';
import test from 'node:test';
import { WATERMARK_TEXT, watermarkTiles } from '../../resources/js/Domains/Editor/Services/previewWatermark.ts';

test('the watermark names the brand in both scripts', () => {
    assert.match(WATERMARK_TEXT, /ميثاق/);
    assert.match(WATERMARK_TEXT, /METHAQ/);
});

test('tiles cover the whole card, even after the grid is rotated', () => {
    const tiles = watermarkTiles(1080, 1920, 430, 128);
    const diagonal = Math.hypot(1080, 1920);
    assert.ok(tiles.some((tile) => tile.x <= -diagonal + 430) && tiles.some((tile) => tile.x >= diagonal - 430));
    assert.ok(tiles.some((tile) => tile.y <= -diagonal + 128) && tiles.some((tile) => tile.y >= diagonal - 128));
    assert.ok(tiles.length > 100);
});

test('alternate rows are staggered so the pattern has no straight columns', () => {
    const rows = new Map<number, number>();
    for (const tile of watermarkTiles(400, 400, 100, 50)) if (!rows.has(tile.y)) rows.set(tile.y, tile.x);
    const starts = [...rows.values()];
    assert.notEqual(starts[0], starts[1]);
    assert.equal(starts[0], starts[2]);
});
