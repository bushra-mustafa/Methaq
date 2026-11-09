import { useEffect, useRef, useState } from 'react';
import { gsap } from 'gsap';
import { useGSAP } from '@gsap/react';
import type { EnvelopeMotionPhase } from '../../../Types/EnvelopeMotion';
import type { MotionPolicy } from '../../../Types/SceneConfig';

gsap.registerPlugin(useGSAP);

export function useEnvelopeMotion(ready: boolean, policy: MotionPolicy) {
    const root = useRef<HTMLDivElement>(null);
    const timeline = useRef<gsap.core.Timeline | null>(null);
    const currentPhase = useRef<EnvelopeMotionPhase>('sealed');
    const [phase, setPhase] = useState<EnvelopeMotionPhase>('sealed');
    const [paused, setPaused] = useState(false);
    const [systemReduced, setSystemReduced] = useState(() => window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    const reduced = systemReduced || policy !== 'system';
    const running = phase !== 'sealed' && phase !== 'opened';

    const changePhase = (next: EnvelopeMotionPhase): void => {
        currentPhase.current = next;
        setPhase(next);
    };

    useEffect(() => {
        const media = window.matchMedia('(prefers-reduced-motion: reduce)');
        const update = (): void => setSystemReduced(media.matches);
        media.addEventListener('change', update);
        return () => media.removeEventListener('change', update);
    }, []);

    useGSAP(() => {
        if (!ready || !root.current) return;
        const select = gsap.utils.selector(root);
        const seal = select('[data-envelope-part="seal"]');
        const flap = select('[data-envelope-part="flap"]');
        const letter = select('[data-envelope-part="letter"]');
        const shell = select('[data-envelope-part="shell"]');
        const cover = select('[data-envelope-part="cover"]');
        const finalCard = select('[data-envelope-part="final"]');
        const shadow = select('[data-envelope-part="shadow"]');

        gsap.set(finalCard, { autoAlpha: 0, y: 30, scale: 0.96 });
        gsap.set([seal, letter, shell, cover], { autoAlpha: 1 });
        gsap.set(flap, { rotationX: 0, zIndex: 5 });
        changePhase('sealed');
        setPaused(false);

        const sequence = gsap.timeline({ paused: true, onComplete: () => { changePhase('opened'); setPaused(false); } });
        sequence
            .to(seal, { y: -18, scale: 1.08, autoAlpha: 0, duration: 0.3, ease: 'power2.in' }, 0)
            .to(cover, { autoAlpha: 0, duration: 0.3 }, 0.3)
            .to(flap, { rotationX: -180, duration: 0.8, ease: 'power2.inOut' }, 0.3)
            .set(flap, { zIndex: 1 }, 0.7)
            .to(shadow, { opacity: 0.1, scaleX: 0.85, duration: 0.8 }, 0.3)
            .to(letter, { yPercent: -62, duration: 0.8, ease: 'power2.out' }, 1.1)
            .call(() => changePhase('names-preview'), [], 1.9)
            .addLabel('names', 1.9)
            .addLabel('reveal', 3.1)
            .call(() => changePhase('revealing'), [], 3.1)
            .set(letter, { zIndex: 7 }, 3.1)
            .to(letter, { yPercent: -108, autoAlpha: 0, duration: 0.6, ease: 'power2.inOut' }, 3.1)
            .to(shell, { y: 70, autoAlpha: 0, duration: 0.6, ease: 'power2.inOut' }, 3.1)
            .to(finalCard, { autoAlpha: 1, y: 0, scale: 1, duration: 0.65, ease: 'power2.out' }, 3.35);
        timeline.current = sequence;

        return () => { timeline.current = null; };
    }, { scope: root, dependencies: [ready], revertOnUpdate: true });

    const skip = (): void => {
        if (!ready || !timeline.current) return;
        timeline.current.progress(1, true).pause();
        changePhase('opened');
        setPaused(false);
    };

    useEffect(() => {
        if (reduced && currentPhase.current !== 'sealed') skip();
    }, [reduced, ready]);

    const open = (): void => {
        if (!ready || currentPhase.current !== 'sealed' || !timeline.current) return;
        if (reduced) { skip(); return; }
        changePhase('opening');
        timeline.current.play(0);
    };

    const replay = (): void => {
        timeline.current?.pause(0, true);
        changePhase('sealed');
        setPaused(false);
    };

    const togglePause = (): void => {
        if (!running || reduced || !timeline.current) return;
        const next = !timeline.current.paused();
        timeline.current.paused(next);
        setPaused(next);
    };

    return { root, phase, paused, reduced, running, open, replay, skip, togglePause };
}
