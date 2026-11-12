import { createSmokeRenderer } from './smokeRenderer';
import type { SmokeRendererHandle } from './smokeRenderer';

/**
 * Living backdrop for the invitation scene: drifting smoke photograph (optional),
 * a breathing ring of light dust, slow star glints and sparkle bursts on tap.
 * Ported from the owner's anas-lamar-invitation reference project and generalised
 * so every backdrop preset can supply its own colours.
 */
export interface AmbienceColors {
    /** Colours used for the small dust squares. */
    dust: [string, string];
    /** Glow sprite tint, from the warm core to the transparent rim. */
    glow: [string, string, string];
    /** Hairline cross drawn over star glints. */
    glint: string;
}

export interface AmbienceElements {
    host: HTMLDivElement;
    interactionRoot: HTMLElement;
    image: HTMLImageElement | null;
    smoke: HTMLCanvasElement;
    flow: HTMLCanvasElement;
    dust: HTMLCanvasElement;
    sparks: HTMLCanvasElement;
}

export interface AmbienceOptions {
    colors: AmbienceColors;
    particleCount: number;
    /** Controls whose taps still produce sparkles (e.g. the wax seal). */
    sparkleSelector: string;
}

export interface InvitationAmbienceHandle {
    setPaused: (paused: boolean) => void;
    destroy: () => void;
}

interface Burst {
    x: number;
    y: number;
    angle: number;
    velocity: number;
    size: number;
    born: number;
}

function withAlpha(hex: string, alpha: number): string {
    const value = hex.replace('#', '');
    const red = Number.parseInt(value.slice(0, 2), 16);
    const green = Number.parseInt(value.slice(2, 4), 16);
    const blue = Number.parseInt(value.slice(4, 6), 16);

    return `rgba(${red},${green},${blue},${alpha})`;
}

export function createInvitationAmbience(
    { host, interactionRoot, image, smoke, flow, dust, sparks }: AmbienceElements,
    { colors, particleCount, sparkleSelector }: AmbienceOptions,
): InvitationAmbienceHandle {
    const dustContext = dust.getContext('2d');
    const sparkContext = sparks.getContext('2d');
    const media = window.matchMedia('(prefers-reduced-motion: reduce)');
    const sprite = document.createElement('canvas');
    sprite.width = 32;
    sprite.height = 32;
    const spriteContext = sprite.getContext('2d');
    if (spriteContext) {
        const glow = spriteContext.createRadialGradient(16, 16, 0, 16, 16, 16);
        glow.addColorStop(0, 'rgba(255,242,222,1)');
        glow.addColorStop(0.12, withAlpha(colors.glow[0], 0.85));
        glow.addColorStop(0.38, withAlpha(colors.glow[1], 0.28));
        glow.addColorStop(1, withAlpha(colors.glow[2], 0));
        spriteContext.fillStyle = glow;
        spriteContext.fillRect(0, 0, 32, 32);
    }

    let seed = 831809;
    const random = (): number => {
        seed = (Math.imul(1664525, seed) + 1013904223) >>> 0;

        return seed / 4294967296;
    };
    const particles = Array.from({ length: particleCount }, () => ({
        angle: random() * Math.PI * 2,
        radius: (random() + random() - 1) * 0.115,
        size: 0.35 + random() * 0.73,
        phase: random() * Math.PI * 2,
        velocity: 0.005 + random() * 0.007,
        light: 0.1 + random() * 0.38,
    }));
    const bursts: Burst[] = [];
    let width = 390;
    let height = 844;
    let dpr = 1;
    let time = 0;
    let sparkleTime = 0;
    let frame = 0;
    let previous = 0;
    let lastPaint = 0;
    let paused = false;
    let destroyed = false;
    let pointerX = 0;
    let pointerY = 0;
    let targetX = 0;
    let targetY = 0;
    let smokeRenderer: SmokeRendererHandle | null = null;

    const resetCanvas = (context: CanvasRenderingContext2D): void => {
        context.setTransform(dpr, 0, 0, dpr, 0, 0);
        context.clearRect(0, 0, width, height);
        context.globalCompositeOperation = 'lighter';
    };

    const paintParticles = (): void => {
        const unit = Math.min(width / 390, 1.5);
        if (dustContext) {
            const context = dustContext;
            resetCanvas(context);
            particles.forEach((particle, index) => {
                const angle = particle.angle + time * particle.velocity;
                const breathing = Math.sin(angle * 4 + time * 0.055) * 0.025;
                const rx = 0.505 + particle.radius + breathing + 0.022 * Math.sin(3 * angle);
                const ry = 0.415 + particle.radius * 0.46 + 0.018 * Math.cos(5 * angle + time * 0.06);
                const x = (0.5 + Math.cos(angle) * rx) * width;
                const y = (0.495 + Math.sin(angle) * ry) * height;
                if (x < -5 || y < -5 || x > width + 5 || y > height + 5) {
                    return;
                }
                const shimmer = 0.38 + 0.62 * ((Math.sin(time * 0.35 + particle.phase) + 1) * 0.5) ** 2;
                context.globalAlpha = particle.light * shimmer;
                if (index % 13 === 0) {
                    const size = (4 + particle.size * 4) * unit;
                    context.drawImage(sprite, x - size / 2, y - size / 2, size, size);
                } else {
                    context.fillStyle = index % 4 === 0 ? colors.dust[0] : colors.dust[1];
                    context.fillRect(x, y, particle.size * unit, particle.size * unit);
                }
            });
            for (let index = 0; index < 8; index++) {
                const angle = index * 2.39996 + time * 0.008;
                const x = (0.5 + Math.cos(angle) * 0.467) * width;
                const y = (0.495 + Math.sin(angle) * 0.413) * height;
                const intensity = Math.max(0, Math.sin(time * 0.24 + index * 2.13)) ** 8;
                if (intensity < 0.02) {
                    continue;
                }
                context.globalAlpha = intensity * 0.48;
                const size = 7 * unit;
                context.drawImage(sprite, x - size, y - size, size * 2, size * 2);
                context.strokeStyle = colors.glint;
                context.lineWidth = 0.5;
                context.beginPath();
                context.moveTo(x - size * 0.4, y);
                context.lineTo(x + size * 0.4, y);
                context.moveTo(x, y - size * 0.8);
                context.lineTo(x, y + size * 0.8);
                context.stroke();
            }
            context.globalAlpha = 1;
            context.globalCompositeOperation = 'source-over';
        }
        if (!sparkContext) {
            return;
        }
        resetCanvas(sparkContext);
        for (let index = bursts.length - 1; index >= 0; index--) {
            const burst = bursts[index];
            if (!burst) {
                continue;
            }
            const age = sparkleTime - burst.born;
            if (age > 3.5) {
                bursts.splice(index, 1);
                continue;
            }
            const spread = 1 - Math.exp(-age * 0.9);
            const x = burst.x + Math.cos(burst.angle) * burst.velocity * spread;
            const y = burst.y + Math.sin(burst.angle) * burst.velocity * spread - age * 7;
            sparkContext.globalAlpha = (1 - age / 3.5) * 0.7;
            const size = burst.size * unit;
            sparkContext.drawImage(sprite, x - size / 2, y - size / 2, size, size);
        }
        sparkContext.globalAlpha = 1;
        sparkContext.globalCompositeOperation = 'source-over';
    };

    const paint = (): void => {
        pointerX += (targetX - pointerX) * 0.018;
        pointerY += (targetY - pointerY) * 0.018;
        smokeRenderer?.renderer.paint(time, pointerX, pointerY);
        paintParticles();
    };

    const resize = (): void => {
        const rectangle = host.getBoundingClientRect();
        width = Math.max(1, rectangle.width);
        height = Math.max(1, rectangle.height);
        dpr = Math.min(window.devicePixelRatio || 1, 1.75);
        for (const canvas of [dust, sparks]) {
            canvas.width = Math.round(width * dpr);
            canvas.height = Math.round(height * dpr);
        }
        const scale = Math.min(0.95, 510 / width, 980 / height);
        for (const canvas of [smoke, flow]) {
            canvas.width = Math.round(width * scale);
            canvas.height = Math.round(height * scale);
        }
        smokeRenderer?.renderer.resize(width, height);
        paint();
    };

    const loadSmoke = (): void => {
        if (destroyed || !image?.naturalWidth) {
            return;
        }
        smokeRenderer?.renderer.destroy();
        smoke.classList.remove('is-ready');
        flow.classList.remove('is-ready');
        smokeRenderer = createSmokeRenderer(smoke, flow, image);
        resize();
        smokeRenderer?.canvas.classList.add('is-ready');
    };

    const loop = (now: number): void => {
        frame = 0;
        if (destroyed || media.matches || document.hidden) {
            return;
        }
        if (paused && bursts.length === 0) {
            return;
        }
        if (!previous) {
            previous = now;
        }
        const delta = Math.min((now - previous) / 1000, 0.08) * 0.75;
        if (!paused) {
            time += delta;
        }
        sparkleTime += delta;
        previous = now;
        if (now - lastPaint >= 1000 / 30) {
            if (paused) {
                paintParticles();
            } else {
                paint();
            }
            lastPaint = now;
        }
        frame = requestAnimationFrame(loop);
    };

    const syncMotion = (): void => {
        cancelAnimationFrame(frame);
        frame = 0;
        previous = 0;
        if (media.matches) {
            bursts.length = 0;
        }
        paint();
        if ((!paused || bursts.length > 0) && !media.matches && !document.hidden && !destroyed) {
            frame = requestAnimationFrame(loop);
        }
    };

    const pointerMove = (event: PointerEvent): void => {
        if (event.pointerType !== 'mouse') {
            return;
        }
        const rectangle = host.getBoundingClientRect();
        targetX = (event.clientX - rectangle.left) / width - 0.5;
        targetY = (event.clientY - rectangle.top) / height - 0.5;
    };

    const pointerLeave = (): void => {
        targetX = 0;
        targetY = 0;
    };

    const pointerDown = (event: PointerEvent): void => {
        if (destroyed || !event.isPrimary || event.button !== 0 || media.matches || !(event.target instanceof Element)) {
            return;
        }
        const control = event.target.closest('button, a, input, select, textarea');
        if (control && !control.matches(sparkleSelector)) {
            return;
        }
        const rectangle = sparks.getBoundingClientRect();
        if (event.clientX < rectangle.left || event.clientX > rectangle.right || event.clientY < rectangle.top || event.clientY > rectangle.bottom) {
            return;
        }
        for (let index = 0; index < 32; index++) {
            bursts.push({
                x: event.clientX - rectangle.left,
                y: event.clientY - rectangle.top,
                angle: random() * Math.PI * 2,
                velocity: 20 + random() * 65,
                size: 3 + random() * 6,
                born: sparkleTime,
            });
        }
        if (bursts.length > 240) {
            bursts.splice(0, bursts.length - 240);
        }
        paintParticles();
        if (!frame && !document.hidden) {
            previous = 0;
            frame = requestAnimationFrame(loop);
        }
    };

    const contextLost = (event: Event): void => {
        event.preventDefault();
        loadSmoke();
    };

    const observer = new ResizeObserver(resize);
    observer.observe(host);
    image?.addEventListener('load', loadSmoke);
    smoke.addEventListener('webglcontextlost', contextLost);
    smoke.addEventListener('webglcontextrestored', loadSmoke);
    interactionRoot.addEventListener('pointermove', pointerMove, { passive: true });
    interactionRoot.addEventListener('pointerleave', pointerLeave);
    interactionRoot.addEventListener('pointerdown', pointerDown, { capture: true, passive: true });
    document.addEventListener('visibilitychange', syncMotion);
    media.addEventListener('change', syncMotion);
    if (image?.complete && image.naturalWidth) {
        loadSmoke();
    }
    resize();
    syncMotion();

    return {
        setPaused(value) {
            paused = value;
            syncMotion();
        },
        destroy() {
            destroyed = true;
            cancelAnimationFrame(frame);
            observer.disconnect();
            image?.removeEventListener('load', loadSmoke);
            smoke.removeEventListener('webglcontextlost', contextLost);
            smoke.removeEventListener('webglcontextrestored', loadSmoke);
            interactionRoot.removeEventListener('pointermove', pointerMove);
            interactionRoot.removeEventListener('pointerleave', pointerLeave);
            interactionRoot.removeEventListener('pointerdown', pointerDown, true);
            document.removeEventListener('visibilitychange', syncMotion);
            media.removeEventListener('change', syncMotion);
            smokeRenderer?.renderer.destroy();
        },
    };
}
