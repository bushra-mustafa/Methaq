import { useEffect, useRef } from 'react';
import type { RefObject } from 'react';
import type { SceneBackdropPreset } from '../../../Types/SceneConfig';
import { createInvitationAmbience } from '../Services/invitationAmbience';
import type { AmbienceColors, InvitationAmbienceHandle } from '../Services/invitationAmbience';

interface AmbiencePreset {
    image: string | null;
    particleCount: number;
    colors: AmbienceColors;
}

const PINK: AmbienceColors = { dust: ['#ffdacf', '#f2b3c1'], glow: ['#ffd1d2', '#fd8eb2', '#f56b9b'], glint: '#ffe6dd' };

/** Living-backdrop settings per scene backdrop; `inherit` stays static. */
const PRESETS: Partial<Record<SceneBackdropPreset, AmbiencePreset>> = {
    'henna-noir': { image: '/editor/backdrops/v1/henna-noir/burgundy-smoke.jpeg', particleCount: 980, colors: PINK },
    'burgundy-nebula': { image: null, particleCount: 620, colors: PINK },
    'blush-cloud': { image: null, particleCount: 560, colors: { dust: ['#fff0f3', '#ffd0db'], glow: ['#ffe3e8', '#f5a3b8', '#d78c9d'], glint: '#fff4f6' } },
    'midnight-gold': { image: null, particleCount: 620, colors: { dust: ['#fff1c9', '#e9c77a'], glow: ['#ffe7a8', '#d9b25c', '#c59a4e'], glint: '#fff4d6' } },
    'emerald-silk': { image: null, particleCount: 560, colors: { dust: ['#e6f6dc', '#b8dcb0'], glow: ['#d9f0c8', '#8fc79a', '#2e896e'], glint: '#effbe9' } },
};

export function hasAmbience(preset: SceneBackdropPreset): boolean {
    return PRESETS[preset] !== undefined;
}

interface Props {
    preset: SceneBackdropPreset;
    paused: boolean;
    interactionRoot: RefObject<HTMLElement | null>;
    sparkleSelector?: string;
}

export function InvitationAmbience({ preset, paused, interactionRoot, sparkleSelector = '.invitation-noir-seal, .invitation-seal' }: Props) {
    const host = useRef<HTMLDivElement>(null);
    const image = useRef<HTMLImageElement>(null);
    const smoke = useRef<HTMLCanvasElement>(null);
    const flow = useRef<HTMLCanvasElement>(null);
    const dust = useRef<HTMLCanvasElement>(null);
    const sparks = useRef<HTMLCanvasElement>(null);
    const scene = useRef<InvitationAmbienceHandle | null>(null);
    const settings = PRESETS[preset];

    useEffect(() => {
        if (!settings || !host.current || !interactionRoot.current || !smoke.current || !flow.current || !dust.current || !sparks.current) {
            return;
        }
        scene.current = createInvitationAmbience(
            { host: host.current, interactionRoot: interactionRoot.current, image: image.current, smoke: smoke.current, flow: flow.current, dust: dust.current, sparks: sparks.current },
            { colors: settings.colors, particleCount: settings.particleCount, sparkleSelector },
        );

        return () => {
            scene.current?.destroy();
            scene.current = null;
        };
    }, [settings, interactionRoot, sparkleSelector]);

    useEffect(() => {
        scene.current?.setPaused(paused);
    }, [paused]);

    if (!settings) {
        return null;
    }

    return <>
        <div ref={host} className={`invitation-ambience ${settings.image ? 'has-smoke' : ''}`} aria-hidden="true">
            {settings.image && <img ref={image} className="invitation-ambience-poster" src={settings.image} alt="" width={576} height={1024} draggable={false} fetchPriority="high" />}
            <canvas ref={flow} className="invitation-ambience-smoke" />
            <canvas ref={smoke} className="invitation-ambience-smoke" />
            {settings.image && <div className="invitation-ambience-veil" />}
            <canvas ref={dust} className="invitation-ambience-dust" />
        </div>
        <canvas ref={sparks} className="invitation-ambience-sparks" aria-hidden="true" />
    </>;
}
