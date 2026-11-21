// Headless Chrome driven over the DevTools protocol, plus a tiny static file server for the render harness.
// All builds share ONE long-lived Chrome (started on demand, found again through a small discovery file) so that
// many designers can render at the same time without launching a browser per build.
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import http from 'node:http';
import os from 'node:os';
import path from 'node:path';

const CHROME_CANDIDATES = [
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    '/Applications/Chromium.app/Contents/MacOS/Chromium',
    '/usr/bin/google-chrome',
    '/usr/bin/google-chrome-stable',
    '/usr/bin/chromium',
    '/usr/bin/chromium-browser',
];
const CHROME = process.env.CHROME_BIN ?? CHROME_CANDIDATES.find((candidate) => fs.existsSync(candidate)) ?? CHROME_CANDIDATES[0];
const PROFILE = path.join(os.tmpdir(), 'methaq-card-designer-chrome-profile');
const DISCOVERY = path.join(os.tmpdir(), 'methaq-card-designer-chrome.json');
const LOCK = path.join(os.tmpdir(), 'methaq-card-designer-chrome.lock');
const PORT = Number(process.env.METHAQ_CHROME_PORT ?? 0);

/** The port Chrome chose for --remote-debugging-port=0, read from the profile it writes it to. */
function activePort() {
    try {
        const port = Number(fs.readFileSync(path.join(PROFILE, 'DevToolsActivePort'), 'utf8').split('\n')[0]);
        return Number.isInteger(port) && port > 0 ? port : null;
    } catch {
        return null;
    }
}

const TYPES = {
    '.html': 'text/html; charset=utf-8',
    '.js': 'text/javascript; charset=utf-8',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.svg': 'image/svg+xml',
    '.ttf': 'font/ttf',
    '.otf': 'font/otf',
    '.woff2': 'font/woff2',
    '.json': 'application/json',
};

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

/** mounts: { '/prefix/': '/absolute/dir' } */
export function startStaticServer(mounts) {
    const server = http.createServer((request, response) => {
        const requested = decodeURIComponent(new URL(request.url ?? '/', 'http://local').pathname);
        for (const [prefix, directory] of Object.entries(mounts)) {
            if (!requested.startsWith(prefix)) continue;
            const file = path.resolve(directory, requested.slice(prefix.length));
            if (!file.startsWith(path.resolve(directory))) break;
            fs.readFile(file, (error, data) => {
                if (error) {
                    response.writeHead(404);
                    response.end('not found');
                    return;
                }
                response.writeHead(200, { 'Content-Type': TYPES[path.extname(file).toLowerCase()] ?? 'application/octet-stream', 'Cache-Control': 'no-store' });
                response.end(data);
            });
            return;
        }
        response.writeHead(404);
        response.end('not found');
    });

    return new Promise((resolve) => {
        server.listen(0, '127.0.0.1', () => {
            resolve({
                port: server.address().port,
                close: () => new Promise((done) => server.close(() => done())),
            });
        });
    });
}

async function versionOf(port) {
    try {
        const response = await fetch(`http://127.0.0.1:${port}/json/version`, { signal: AbortSignal.timeout(1500) });
        if (!response.ok) return null;
        return (await response.json()).webSocketDebuggerUrl ?? null;
    } catch {
        return null;
    }
}

function readDiscovery() {
    try { return JSON.parse(fs.readFileSync(DISCOVERY, 'utf8')); } catch { return null; }
}

/** Finds the shared Chrome or starts it; returns its browser WebSocket endpoint. */
async function ensureBrowser() {
    const known = readDiscovery();
    if (known) {
        const endpoint = await versionOf(known.port);
        if (endpoint) return endpoint;
    }

    // Only one process may start the browser; the others wait for it.
    const deadline = Date.now() + 60_000;
    for (;;) {
        try {
            fs.mkdirSync(LOCK);
            break;
        } catch {
            const current = readDiscovery();
            if (current) {
                const endpoint = await versionOf(current.port);
                if (endpoint) return endpoint;
            }
            try {
                if (Date.now() - fs.statSync(LOCK).mtimeMs > 45_000) fs.rmSync(LOCK, { recursive: true, force: true });
            } catch { /* lock vanished */ }
            if (Date.now() > deadline) throw new Error('Timed out waiting for the shared Chrome to start');
            await sleep(300);
        }
    }

    try {
        const again = readDiscovery();
        if (again) {
            const endpoint = await versionOf(again.port);
            if (endpoint) return endpoint;
        }
        // Port 0 lets Chrome pick a free port and write it to DevToolsActivePort, so a stale browser that still
        // holds a fixed port (on IPv4 or IPv6) can never capture the connection.
        if (!fs.existsSync(CHROME)) throw new Error(`Chrome not found at ${CHROME}; set CHROME_BIN`);
        fs.rmSync(path.join(PROFILE, 'DevToolsActivePort'), { force: true });
        const child = spawn(CHROME, [
            '--headless=new',
            '--disable-gpu',
            '--no-first-run',
            '--no-default-browser-check',
            '--hide-scrollbars',
            '--force-device-scale-factor=1',
            '--disable-background-timer-throttling',
            '--disable-renderer-backgrounding',
            `--remote-debugging-port=${PORT}`,
            `--user-data-dir=${PROFILE}`,
            'about:blank',
        ], { stdio: 'ignore', detached: true });
        child.unref();
        for (let attempt = 0; attempt < 120; attempt += 1) {
            const port = PORT === 0 ? activePort() : PORT;
            const endpoint = port ? await versionOf(port) : null;
            if (endpoint) {
                fs.writeFileSync(DISCOVERY, JSON.stringify({ port, pid: child.pid }));
                return endpoint;
            }
            await sleep(250);
        }
        try { process.kill(child.pid, 'SIGKILL'); } catch { /* already gone */ }
        throw new Error('Could not start headless Chrome (no DevTools endpoint after 30s)');
    } finally {
        fs.rmSync(LOCK, { recursive: true, force: true });
    }
}

/** A connection to the shared browser. release() closes only this client's tabs, never the browser. */
export async function acquireChrome() {
    const endpoint = await ensureBrowser();
    const socket = new WebSocket(endpoint);
    await new Promise((resolve, reject) => {
        socket.addEventListener('open', resolve, { once: true });
        socket.addEventListener('error', () => reject(new Error('DevTools socket failed')), { once: true });
    });

    let sequence = 0;
    const pending = new Map();
    const targets = new Set();
    socket.addEventListener('message', (event) => {
        const message = JSON.parse(event.data);
        if (message.id !== undefined && pending.has(message.id)) {
            const { resolve, reject } = pending.get(message.id);
            pending.delete(message.id);
            if (message.error) reject(new Error(message.error.message));
            else resolve(message.result);
        }
    });
    socket.addEventListener('close', () => {
        for (const { reject } of pending.values()) reject(new Error('DevTools socket closed'));
        pending.clear();
    });

    const send = (method, params = {}, sessionId) => new Promise((resolve, reject) => {
        sequence += 1;
        pending.set(sequence, { resolve, reject });
        socket.send(JSON.stringify({ id: sequence, method, params, ...(sessionId ? { sessionId } : {}) }));
    });

    async function openPage(url) {
        const { targetId } = await send('Target.createTarget', { url });
        targets.add(targetId);
        const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
        await send('Runtime.enable', {}, sessionId);
        return {
            async evaluate(expression, timeoutMs = 120_000) {
                const run = send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true }, sessionId);
                const timeout = new Promise((_, reject) => setTimeout(() => reject(new Error(`page evaluation timed out after ${timeoutMs}ms`)), timeoutMs));
                const result = await Promise.race([run, timeout]);
                if (result.exceptionDetails) {
                    throw new Error(result.exceptionDetails.exception?.description ?? result.exceptionDetails.text);
                }
                return result.result.value;
            },
            close: async () => {
                targets.delete(targetId);
                await send('Target.closeTarget', { targetId }).catch(() => undefined);
            },
        };
    }

    async function release() {
        for (const targetId of [...targets]) await send('Target.closeTarget', { targetId }).catch(() => undefined);
        targets.clear();
        try { socket.close(); } catch { /* already closed */ }
    }

    return { openPage, close: release, release };
}

export const launchChrome = acquireChrome;

/** Opens the harness page served by startStaticServer and waits until window.Methaq is ready. */
export async function openHarness(chrome, port) {
    const page = await chrome.openPage(`http://127.0.0.1:${port}/harness/harness.html`);
    const deadline = Date.now() + 30_000;
    while (Date.now() < deadline) {
        const ready = await page.evaluate('Boolean(window.Methaq && window.Methaq.ready)', 8_000).catch(() => false);
        if (ready) return page;
        await sleep(150);
    }
    throw new Error('Render harness did not become ready');
}

/** Stops the shared browser (used by the orchestrator when all builds are finished). */
export async function stopSharedChrome() {
    const known = readDiscovery();
    if (!known) return false;
    try { process.kill(known.pid, 'SIGKILL'); } catch { /* already gone */ }
    fs.rmSync(DISCOVERY, { force: true });
    await sleep(500);
    fs.rmSync(PROFILE, { recursive: true, force: true, maxRetries: 6, retryDelay: 250 });
    return true;
}
