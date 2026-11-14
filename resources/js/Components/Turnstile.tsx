import { useEffect, useRef } from 'react';

interface TurnstileApi {
    render(container: HTMLElement, options: Record<string, unknown>): string;
    remove(widgetId: string): void;
}

declare global {
    interface Window {
        turnstile?: TurnstileApi;
    }
}

const SCRIPT_ID = 'cf-turnstile-script';
const SCRIPT_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

function loadScript(): Promise<TurnstileApi> {
    return new Promise((resolve, reject) => {
        if (window.turnstile) {
            resolve(window.turnstile);
            return;
        }
        const existing = document.getElementById(SCRIPT_ID);
        const script = existing ?? Object.assign(document.createElement('script'), { id: SCRIPT_ID, src: SCRIPT_SRC, async: true, defer: true });
        script.addEventListener('load', () => (window.turnstile ? resolve(window.turnstile) : reject(new Error('Turnstile did not start.'))));
        script.addEventListener('error', () => reject(new Error('Turnstile could not be loaded.')));
        if (!existing) document.head.appendChild(script);
    });
}

interface TurnstileProps {
    siteKey: string;
    /** Called with the one-time token, or with '' when it expired or failed. */
    onToken: (token: string) => void;
    /** Change this value to draw a fresh challenge (a token works once). */
    round: number;
    error?: string;
}

/** Cloudflare Turnstile widget. Renders nothing visible until Cloudflare decides a challenge is needed. */
export default function Turnstile({ siteKey, onToken, round, error }: TurnstileProps) {
    const container = useRef<HTMLDivElement>(null);
    const callback = useRef(onToken);
    callback.current = onToken;

    useEffect(() => {
        let widgetId: string | null = null;
        let cancelled = false;
        callback.current('');

        void loadScript()
            .then((turnstile) => {
                if (cancelled || !container.current) return;
                widgetId = turnstile.render(container.current, {
                    sitekey: siteKey,
                    language: 'ar',
                    theme: 'light',
                    callback: (token: string) => callback.current(token),
                    'expired-callback': () => callback.current(''),
                    'error-callback': () => callback.current(''),
                });
            })
            .catch(() => callback.current(''));

        return () => {
            cancelled = true;
            if (widgetId !== null) window.turnstile?.remove(widgetId);
        };
    }, [siteKey, round]);

    return <div>
        <div ref={container} />
        {error && <p role="alert" className="m-0 mt-2 text-sm text-[#8a2b2b]">{error}</p>}
    </div>;
}
