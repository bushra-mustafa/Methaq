import { useCallback, useEffect, useRef, useState } from 'react';
import type { EditorAsset } from '../../../Types/Editor';

interface SamplerState { trackId: string | null; failed: boolean }

/**
 * Plays one library track at a time so the owner can hear it before choosing it. The invitation's own sound is never touched.
 * While `muted` (the full preview is open) nothing is sampled, so two sounds never overlap.
 */
export function useTrackSampler(volume: number, muted: boolean) {
    const element = useRef<HTMLAudioElement | null>(null);
    const request = useRef(0);
    const [state, setState] = useState<SamplerState>({ trackId: null, failed: false });

    const release = useCallback((): void => {
        request.current++;
        const current = element.current;
        element.current = null;
        if (!current) return;
        current.pause();
        current.removeAttribute('src');
        current.load();
    }, []);
    const stop = useCallback((): void => {
        release();
        setState({ trackId: null, failed: false });
    }, [release]);

    useEffect(() => release, [release]);
    useEffect(() => { if (muted) stop(); }, [muted, stop]);
    useEffect(() => { if (element.current) element.current.volume = volume; }, [volume]);

    const toggle = useCallback((track: EditorAsset): void => {
        if (muted) return;
        if (state.trackId === track.id && !state.failed) { stop(); return; }
        release();
        const token = request.current;
        const failed = (): void => { if (token === request.current) setState({ trackId: track.id, failed: true }); };
        const sample = new Audio(track.previewUrl);
        sample.volume = volume;
        sample.addEventListener('ended', () => { if (token === request.current) stop(); });
        sample.addEventListener('error', failed);
        element.current = sample;
        setState({ trackId: track.id, failed: false });
        void sample.play().catch(failed);
    }, [muted, release, state, stop, volume]);

    return { playingId: state.failed ? null : state.trackId, failedId: state.failed ? state.trackId : null, toggle };
}
