import assert from 'node:assert/strict';
import { afterEach, test } from 'node:test';
import { uploadTemplateThumbnail } from '../../resources/js/Domains/Editor/Services/templateThumbnailClient.ts';

/** A 1x1 PNG, enough for the client: only the type and the bytes matter here. */
const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

interface SentRequest {
    url: string;
    init: RequestInit | undefined;
}

const realFetch = globalThis.fetch;
const realDocument = (globalThis as { document?: unknown }).document;
let sent: SentRequest[] = [];

function respond(status: number): void {
    sent = [];
    (globalThis as { document?: unknown }).document = { cookie: 'XSRF-TOKEN=abc%3D' };
    globalThis.fetch = (async (input: string | URL | Request, init?: RequestInit) => {
        sent.push({ url: String(input), init });
        return new Response(null, { status });
    }) as typeof fetch;
}

afterEach(() => {
    globalThis.fetch = realFetch;
    (globalThis as { document?: unknown }).document = realDocument;
});

test('the studio preview is decoded in the page and posted once, never fetched as a data URL', async () => {
    respond(200);

    await uploadTemplateThumbnail('12', `data:image/png;base64,${PNG_BASE64}`);

    assert.equal(sent.length, 1);
    assert.equal(sent[0]?.url, '/app/studio/templates/12/thumbnail');
    assert.equal(sent[0]?.init?.method, 'POST');
    assert.deepEqual(sent[0]?.init?.headers, { Accept: 'application/json', 'X-XSRF-TOKEN': 'abc=' });
    const body = sent[0]?.init?.body;
    assert.ok(body instanceof FormData);
    const preview = body.get('preview');
    assert.ok(preview instanceof File);
    assert.equal(preview.name, 'preview.png');
    assert.equal(preview.type, 'image/png');
    assert.deepEqual(new Uint8Array(await preview.arrayBuffer()), Uint8Array.from(atob(PNG_BASE64), (character) => character.charCodeAt(0)));
});

test('an empty canvas capture is refused before any request is sent', async () => {
    respond(200);

    await assert.rejects(uploadTemplateThumbnail('12', 'data:,'));
    assert.equal(sent.length, 0);
});
