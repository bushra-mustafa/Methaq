import assert from 'node:assert/strict';
import test from 'node:test';
import { TEXT_WRAP_OPTIONS, textLineHeight } from '../../resources/js/Domains/Editor/Services/textWrapping.ts';

test('canvas text wraps at spaces, never inside an Arabic word', () => {
    assert.equal(TEXT_WRAP_OPTIONS.splitByGrapheme, false);
});

test('Quranic text gets looser lines than plain text', () => {
    assert.ok(textLineHeight('font-amiri-quran') > textLineHeight('font-amiri'));
    assert.equal(textLineHeight(undefined), 1.25);
});
