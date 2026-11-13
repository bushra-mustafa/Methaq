import assert from 'node:assert/strict';
import test from 'node:test';
import { CONSENT_COOKIE, parseConsent, serializeConsent } from '../../resources/js/Domains/Privacy/consent.ts';

test('an unanswered visitor has no saved choice', () => {
    assert.equal(parseConsent(''), null);
    assert.equal(parseConsent('methaq_app_session=abc; XSRF-TOKEN=xyz'), null);
});

test('only the two known answers are read, from among other cookies', () => {
    assert.equal(parseConsent(`a=1; ${CONSENT_COOKIE}=accepted; b=2`), 'accepted');
    assert.equal(parseConsent(`${CONSENT_COOKIE}=rejected`), 'rejected');
    assert.equal(parseConsent(`${CONSENT_COOKIE}=maybe`), null);
    assert.equal(parseConsent(`x${CONSENT_COOKIE}=accepted`), null);
});

test('the saved answer is a first-party, lax, 180-day cookie that is secure on https', () => {
    const cookie = serializeConsent('accepted', true);
    assert.match(cookie, /^methaq_consent=accepted; Max-Age=15552000; Path=\/; SameSite=Lax; Secure$/);
    assert.doesNotMatch(serializeConsent('rejected', false), /Secure/);
    assert.equal(parseConsent(serializeConsent('rejected', false).split(';')[0] ?? ''), 'rejected');
});
