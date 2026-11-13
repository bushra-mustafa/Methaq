import assert from 'node:assert/strict';
import { afterEach, test } from 'node:test';
import {
    DesignSaveConflictError,
    DesignSaveError,
    saveDesign,
} from '../../resources/js/Domains/Editor/Services/designSaveClient.ts';

const documentFixture = {
    schemaVersion: 1,
    canvas: { schemaVersion: 1, width: 1080, height: 1920, background: { source: 'palette', role: 'background' }, layers: [] },
    palette: { schemaVersion: 1, values: { background: '#ead0d0', surface: '#fffaf1', primaryText: '#866143', secondaryText: '#6f5949', accent: '#a78550', effect: '#fff5da' } },
    scene: {
        sceneSchemaVersion: 1,
        opening: { type: 'direct', durationMs: 0 },
        effects: [],
        audio: { enabled: false, asset: null, volume: 0.6 },
        motionPolicy: 'system',
    },
};

const realFetch = globalThis.fetch;
const realDocument = (globalThis as { document?: unknown }).document;

function respond(status: number, body: unknown): void {
    (globalThis as { document?: unknown }).document = { cookie: 'XSRF-TOKEN=abc%3D' };
    globalThis.fetch = (async () => new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } })) as typeof fetch;
}

afterEach(() => {
    globalThis.fetch = realFetch;
    (globalThis as { document?: unknown }).document = realDocument;
});

test('a save returns the snapshot the server confirms', async () => {
    respond(200, { document: documentFixture, revision: 4 });

    const snapshot = await saveDesign('/save', { document: documentFixture as never, expectedRevision: 3 }, new AbortController().signal);

    assert.equal(snapshot.revision, 4);
});

test('a second tab saving a stale revision gets the newer snapshot back as a conflict', async () => {
    respond(409, { message: 'newer', snapshot: { document: documentFixture, revision: 7 } });

    await assert.rejects(
        saveDesign('/save', { document: documentFixture as never, expectedRevision: 3 }, new AbortController().signal),
        (error: unknown) => error instanceof DesignSaveConflictError && error.snapshot.revision === 7,
    );
});

test('losing the connection keeps the work local and says so', async () => {
    (globalThis as { document?: unknown }).document = { cookie: '' };
    globalThis.fetch = (async () => { throw new TypeError('network down'); }) as typeof fetch;

    await assert.rejects(
        saveDesign('/save', { document: documentFixture as never, expectedRevision: 3 }, new AbortController().signal),
        (error: unknown) => error instanceof DesignSaveError && error.status === null && /محلياً/.test(error.message),
    );
});

test('a server refusal carries its status and message, not a conflict', async () => {
    respond(422, { message: 'التصميم غير صالح.' });

    await assert.rejects(
        saveDesign('/save', { document: documentFixture as never, expectedRevision: 3 }, new AbortController().signal),
        (error: unknown) => error instanceof DesignSaveError && !(error instanceof DesignSaveConflictError) && error.status === 422 && error.message === 'التصميم غير صالح.',
    );
});

test('an aborted save is passed through untouched so a newer save can replace it', async () => {
    (globalThis as { document?: unknown }).document = { cookie: '' };
    globalThis.fetch = (async () => { throw new DOMException('aborted', 'AbortError'); }) as typeof fetch;

    await assert.rejects(
        saveDesign('/save', { document: documentFixture as never, expectedRevision: 3 }, new AbortController().signal),
        (error: unknown) => error instanceof DOMException && error.name === 'AbortError',
    );
});

test('the CSRF token from the cookie is sent with every save', async () => {
    let sent = '';
    (globalThis as { document?: unknown }).document = { cookie: 'a=1; XSRF-TOKEN=tok%3D; b=2' };
    globalThis.fetch = (async (_url: unknown, init?: RequestInit) => {
        sent = String((init?.headers as Record<string, string>)['X-XSRF-TOKEN']);
        return new Response(JSON.stringify({ document: documentFixture, revision: 2 }), { status: 200 });
    }) as typeof fetch;

    await saveDesign('/save', { document: documentFixture as never, expectedRevision: 1 }, new AbortController().signal);

    assert.equal(sent, 'tok=');
});
