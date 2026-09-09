import { useEffect, useRef, useState } from 'react';
import type { AudioConfig } from '../../../Types/SceneConfig';
import type { EditorAsset } from '../../../Types/Editor';

type AudioState = 'idle' | 'loading' | 'playing' | 'paused' | 'blocked' | 'error';
export function useInvitationAudio(config: AudioConfig, assets: EditorAsset[]) {
    const audio = useRef<HTMLAudioElement | null>(null);
    const request = useRef(0);
    const [state, setState] = useState<AudioState>('idle');
    const asset = assets.find((item) => item.type === 'audio' && item.id === config.asset?.assetId && item.version === config.asset.version);
    const url = config.enabled ? asset?.previewUrl : undefined;
    useEffect(() => {
        setState('idle');
        if (!url) return;
        const element = new Audio(url);
        element.preload = 'none'; element.loop = true;
        audio.current = element;
        const playing = (): void => setState('playing');
        const waiting = (): void => setState('loading');
        const error = (): void => setState('error');
        element.addEventListener('playing', playing); element.addEventListener('waiting', waiting); element.addEventListener('error', error);
        return () => {
            request.current++; element.pause(); element.removeEventListener('playing', playing); element.removeEventListener('waiting', waiting); element.removeEventListener('error', error);
            element.removeAttribute('src'); element.load(); audio.current = null;
        };
    }, [url]);
    useEffect(() => { if (audio.current) audio.current.volume = config.volume; }, [config.volume, url]);
    const play = async (): Promise<void> => {
        if (!audio.current) return;
        const token = ++request.current;
        setState('loading');
        try { await audio.current.play(); if (token === request.current) setState('playing'); }
        catch (error: unknown) { if (token === request.current) setState(error instanceof DOMException && error.name === 'NotAllowedError' ? 'blocked' : 'error'); }
    };
    const pause = (): void => { request.current++; audio.current?.pause(); setState('paused'); };
    const reset = (): void => { pause(); if (audio.current) audio.current.currentTime = 0; setState('idle'); };
    return { state, available: Boolean(url), missing: config.enabled && !url, play, pause, reset };
}
