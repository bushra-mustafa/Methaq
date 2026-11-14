import assert from 'node:assert/strict';
import test from 'node:test';
import { finalValue, isNumberText, liveValue } from '../../resources/js/Domains/Editor/Services/numberInput.ts';
import { normalizeTextScale } from '../../resources/js/Domains/Editor/Services/textScaling.ts';

test('typing 35 into a size box that needs at least 8 is not forced to 8 on the first digit', () => {
    assert.equal(liveValue('3', 8, 512), null);
    assert.equal(liveValue('35', 8, 512), 35);
    assert.equal(finalValue('3', 8, 512, 40), 8);
});

test('an emptied or half-typed box never becomes 0 or jumps', () => {
    for (const text of ['', '-', '.', '0.', '-.']) assert.equal(liveValue(text, -100, 100), null);
    assert.equal(finalValue('', -100, 100, 42), 42);
    assert.equal(liveValue('-5', -100, 100), -5);
    assert.equal(liveValue('150', -100, 100), null);
    assert.equal(finalValue('150', -100, 100, 0), 100);
});

test('only number characters are accepted', () => {
    assert.ok(isNumberText('-12.5'));
    assert.ok(isNumberText(''));
    assert.ok(!isNumberText('1a'));
    assert.ok(!isNumberText('1-2'));
});

test('a corner drag changes the font size and a side drag changes the width, never stretching the text', () => {
    assert.deepEqual(normalizeTextScale({ width: 400, fontSize: 40, scaleX: 1.5, scaleY: 1.5 }, 4000), { width: 600, fontSize: 60 });
    assert.deepEqual(normalizeTextScale({ width: 400, fontSize: 40, scaleX: 0.5, scaleY: 1 }, 4000), { width: 200, fontSize: 40 });
    assert.equal(normalizeTextScale({ width: 400, fontSize: 40, scaleX: 20, scaleY: 20 }, 4000).fontSize, 512);
    assert.equal(normalizeTextScale({ width: 400, fontSize: 40, scaleX: 0.01, scaleY: 0.01 }, 4000).fontSize, 8);
});
