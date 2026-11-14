import assert from 'node:assert/strict';
import test from 'node:test';
import { isOpen, remainingLabel } from '../../resources/js/Domains/Payments/Services/offerTime.ts';

const now = Date.parse('2026-11-14T10:00:00Z');
const at = (ms: number): string => new Date(now + ms).toISOString();
const HOUR = 3_600_000;

test('the countdown says days and hours, then hours and minutes, then minutes', () => {
    assert.equal(remainingLabel(at(2 * 24 * HOUR + 5 * HOUR), now), 'يومين و5 ساعات');
    assert.equal(remainingLabel(at(24 * HOUR + HOUR), now), 'يوم وساعة');
    assert.equal(remainingLabel(at(3 * 24 * HOUR), now), '3 أيام');
    assert.equal(remainingLabel(at(2 * HOUR + 30 * 60_000), now), 'ساعتين و30 دقيقة');
    assert.equal(remainingLabel(at(20 * 60_000), now), '20 دقيقة');
    assert.equal(remainingLabel(at(5 * 60_000), now), '5 دقائق');
});

test('an offer that has ended gives no label and is closed', () => {
    assert.equal(remainingLabel(at(-1), now), '');
    assert.equal(remainingLabel('not a date', now), '');
    assert.equal(isOpen(at(1000), now), true);
    assert.equal(isOpen(at(0), now), false);
});
