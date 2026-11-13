export type ConsentChoice = 'accepted' | 'rejected';

export const CONSENT_COOKIE = 'methaq_consent';

const MAX_AGE_SECONDS = 60 * 60 * 24 * 180;

/** The visitor's saved answer about optional statistics, or null when she has not answered yet. */
export function parseConsent(cookieHeader: string): ConsentChoice | null {
    for (const part of cookieHeader.split(';')) {
        const [name, value] = part.trim().split('=');
        if (name === CONSENT_COOKIE && (value === 'accepted' || value === 'rejected')) {
            return value;
        }
    }

    return null;
}

/** The Set-Cookie text that remembers an answer for 180 days, first-party and without tracking value. */
export function serializeConsent(choice: ConsentChoice, secure: boolean): string {
    return `${CONSENT_COOKIE}=${choice}; Max-Age=${MAX_AGE_SECONDS}; Path=/; SameSite=Lax${secure ? '; Secure' : ''}`;
}

export function readConsent(): ConsentChoice | null {
    try {
        return parseConsent(document.cookie);
    } catch {
        return null;
    }
}

export function writeConsent(choice: ConsentChoice): void {
    try {
        document.cookie = serializeConsent(choice, window.location.protocol === 'https:');
    } catch {
        /* Cookies blocked: the answer simply is not remembered. */
    }
}
