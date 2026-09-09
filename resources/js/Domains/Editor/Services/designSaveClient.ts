import { parseDesignSnapshot, parseSaveDesignPayload } from './DesignDocumentSerializer';
import type { DesignSnapshot, SaveDesignPayload } from '../../../Types/DesignDocument';

interface JsonResponse {
    ok: boolean;
    status: number;
    json(): Promise<unknown>;
}

export class DesignSaveConflictError extends Error {
    public constructor(public readonly snapshot: DesignSnapshot) {
        super('حُفظ تعديل أحدث من جلسة أخرى.');
        this.name = 'DesignSaveConflictError';
    }
}

export class DesignSaveError extends Error {
    public constructor(message: string, public readonly status: number | null = null) {
        super(message);
        this.name = 'DesignSaveError';
    }
}

function csrfToken(): string {
    const cookie = document.cookie.split('; ').find((item) => item.startsWith('XSRF-TOKEN='));
    if (!cookie) return '';

    return decodeURIComponent(cookie.slice('XSRF-TOKEN='.length));
}

function record(value: unknown): Record<string, unknown> | null {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
        ? value as Record<string, unknown>
        : null;
}

function responseMessage(value: unknown): string | null {
    const payload = record(value);
    if (typeof payload?.message === 'string' && payload.message.length <= 300) return payload.message;

    return null;
}

async function readJson(response: JsonResponse): Promise<unknown> {
    try {
        return await response.json();
    } catch {
        return null;
    }
}

export async function saveDesign(endpoint: string, payload: SaveDesignPayload, signal: AbortSignal): Promise<DesignSnapshot> {
    const validated = parseSaveDesignPayload(payload);
    let response: Response;
    try {
        response = await fetch(endpoint, {
            method: 'PATCH',
            credentials: 'same-origin',
            signal,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(validated),
        });
    } catch (error: unknown) {
        if (error instanceof DOMException && error.name === 'AbortError') throw error;
        throw new DesignSaveError('تعذّر الاتصال بالخادم. احتفظنا بتعديلاتك محلياً.');
    }

    const body = await readJson(response);
    if (response.status === 409) {
        const payloadRecord = record(body);
        throw new DesignSaveConflictError(parseDesignSnapshot(payloadRecord?.snapshot));
    }
    if (!response.ok) {
        throw new DesignSaveError(responseMessage(body) ?? 'تعذّر حفظ التصميم. حاولي مرة أخرى.', response.status);
    }

    return parseDesignSnapshot(body);
}
