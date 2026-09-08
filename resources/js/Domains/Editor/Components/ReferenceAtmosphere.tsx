import { useEffect, useRef } from 'react';

interface Props { color: string; intensity: number; motion: boolean }
// Elliptical dust distribution adapted from the burgundy reference; no tinted image dependency.
export function ReferenceAtmosphere({ color, intensity, motion }: Props) {
    const host = useRef<HTMLCanvasElement>(null);
    useEffect(() => {
        const canvas = host.current;
        const context = canvas?.getContext('2d');
        if (!canvas || !context) return;
        const media = window.matchMedia('(prefers-reduced-motion: reduce)');
        let width = 360, height = 640, frame = 0, time = 0, last = 0;
        let seed = 831809;
        const random = () => { seed = (Math.imul(1664525, seed) + 1013904223) >>> 0; return seed / 4294967296; };
        const particles = Array.from({ length: 700 }, () => ({ angle: random() * Math.PI * 2, radius: (random() + random() - 1) * 0.115, size: 0.35 + random() * 0.73, phase: random() * Math.PI * 2, velocity: 0.005 + random() * 0.007, light: 0.2 + random() * 0.55 }));
        function paint() {
            if (!context) return;
            context.clearRect(0, 0, width, height);
            context.fillStyle = color;
            context.strokeStyle = color;
            for (const [index, particle] of particles.entries()) {
                const angle = particle.angle + time * particle.velocity;
                const breathing = Math.sin(angle * 4 + time * 0.055) * 0.025;
                const x = (0.5 + Math.cos(angle) * (0.505 + particle.radius + breathing + 0.022 * Math.sin(3 * angle))) * width;
                const y = (0.495 + Math.sin(angle) * (0.415 + particle.radius * 0.46 + 0.018 * Math.cos(5 * angle + time * 0.06))) * height;
                context.globalAlpha = intensity * particle.light * (0.38 + 0.62 * ((Math.sin(time * 0.35 + particle.phase) + 1) * 0.5) ** 2);
                const size = particle.size * Math.min(width / 390, 1.5);
                context.fillRect(x, y, size, size);
                if (index % 47 === 0) {
                    context.beginPath(); context.moveTo(x - 3, y); context.lineTo(x + 3, y); context.moveTo(x, y - 5); context.lineTo(x, y + 5); context.lineWidth = 0.7; context.stroke();
                }
            }
            context.globalAlpha = 1;
        }
        function loop(now: number) {
            if (now - last >= 1000 / 30) { time += Math.min((now - (last || now)) / 1000, 0.08); last = now; paint(); }
            frame = requestAnimationFrame(loop);
        }
        function sync() {
            cancelAnimationFrame(frame); last = 0; paint();
            if (motion && !media.matches && !document.hidden && intensity > 0) frame = requestAnimationFrame(loop);
        }
        function resize() {
            if (!canvas || !context) return;
            const bounds = canvas.getBoundingClientRect(); width = bounds.width; height = bounds.height;
            const ratio = Math.min(window.devicePixelRatio || 1, 1.5);
            canvas.width = Math.max(1, Math.round(width * ratio)); canvas.height = Math.max(1, Math.round(height * ratio));
            context.setTransform(ratio, 0, 0, ratio, 0, 0); paint();
        }
        const observer = new ResizeObserver(resize);
        observer.observe(canvas); resize(); sync();
        media.addEventListener('change', sync); document.addEventListener('visibilitychange', sync);
        return () => { observer.disconnect(); cancelAnimationFrame(frame); media.removeEventListener('change', sync); document.removeEventListener('visibilitychange', sync); };
    }, [color, intensity, motion]);
    return <canvas ref={host} className="reference-dust" aria-hidden="true" />;
}
