import { useEffect, useState } from 'react';
import type { OpeningConfig, MotionPolicy } from '../../../Types/SceneConfig';

type Phase = 'sealed' | 'opening' | 'names-preview' | 'opened';

const NAMES_PREVIEW_DURATION_MS = 900;

export function useInvitationOpening(opening: OpeningConfig, policy: MotionPolicy) {
    const [phase, setPhase] = useState<Phase>(opening.type === 'direct' ? 'opened' : 'sealed');
    const [systemReduced, setSystemReduced] = useState(() => window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    const [paused, setPaused] = useState(false);
    const motion = policy !== 'off' && !paused && (policy === 'reduced' || !systemReduced);
    useEffect(() => {
        const media = window.matchMedia('(prefers-reduced-motion: reduce)');
        const update = (): void => setSystemReduced(media.matches);
        media.addEventListener('change', update);
        return () => media.removeEventListener('change', update);
    }, []);
    useEffect(() => {
        if (phase !== 'opening' && phase !== 'names-preview') return;
        const duration = phase === 'opening' ? opening.durationMs : NAMES_PREVIEW_DURATION_MS;
        const timer = window.setTimeout(() => setPhase(phase === 'opening' && opening.type === 'envelope' ? 'names-preview' : 'opened'), motion ? duration : 0);
        return () => window.clearTimeout(timer);
    }, [phase, motion, opening.durationMs]);
    return {
        phase, motion, paused, motionAvailable: policy !== 'off' && (policy === 'reduced' || !systemReduced),
        open: (): void => setPhase(motion ? 'opening' : 'opened'),
        skip: (): void => setPhase('opened'),
        replay: (): void => setPhase(opening.type === 'direct' ? 'opened' : 'sealed'),
        toggleMotion: (): void => setPaused((value) => !value),
    };
}
