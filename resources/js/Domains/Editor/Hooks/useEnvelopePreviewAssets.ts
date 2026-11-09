import { useEffect, useState } from 'react';

type AssetStatus = 'loading' | 'ready' | 'error';
const LOAD_TIMEOUT_MS = 15000;

export function useEnvelopePreviewAssets(cardUrl: string, names: string) {
    const [attempt, setAttempt] = useState(0);
    const [status, setStatus] = useState<AssetStatus>('loading');

    useEffect(() => {
        let active = true;
        const image = new Image();
        setStatus('loading');
        const timeout = window.setTimeout(() => {
            if (active) { active = false; setStatus('error'); }
        }, LOAD_TIMEOUT_MS);
        image.src = cardUrl;
        void Promise.all([
            image.decode(),
            document.fonts.load('32px "Envelope Amiri"', names),
            document.fonts.load('32px "Envelope Pinyon"', names),
            document.fonts.ready,
        ]).then(() => {
            if (active) setStatus('ready');
        }).catch(() => {
            if (active) setStatus('error');
        }).finally(() => window.clearTimeout(timeout));

        return () => {
            active = false;
            window.clearTimeout(timeout);
            image.src = '';
        };
    }, [cardUrl, names, attempt]);

    return { status, retry: (): void => setAttempt((value) => value + 1) };
}
