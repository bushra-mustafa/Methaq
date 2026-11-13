import { useId } from "react";

/**
 * Forest-green silk drawn in SVG: stretched turbulence used as a height map under a distant
 * light gives long soft folds, then a warm light pool and a vignette add depth. No images.
 */
export default function SilkBackdrop({ className = "" }: { className?: string }) {
    const id = useId().replace(/:/g, "");

    return (
        <svg
            aria-hidden="true"
            focusable="false"
            preserveAspectRatio="none"
            className={`pointer-events-none absolute inset-0 -z-10 size-full ${className}`}
        >
            <defs>
                <linearGradient id={`${id}-base`} x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stopColor="#2d4733" />
                    <stop offset="0.55" stopColor="#203322" />
                    <stop offset="1" stopColor="#142218" />
                </linearGradient>
                <filter id={`${id}-folds`} x="0" y="0" width="100%" height="100%">
                    <feTurbulence type="fractalNoise" baseFrequency="0.0022 0.014" numOctaves="3" seed="9" result="noise" />
                    <feColorMatrix in="noise" type="matrix" values="0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 1.4 -0.2" result="height" />
                    <feDiffuseLighting in="height" surfaceScale="10" diffuseConstant="1.05" lightingColor="#c4dcc8">
                        <feDistantLight azimuth="232" elevation="46" />
                    </feDiffuseLighting>
                </filter>
                <radialGradient id={`${id}-pool`} cx="30%" cy="22%" r="62%">
                    <stop offset="0" stopColor="#e2c59d" stopOpacity="0.26" />
                    <stop offset="1" stopColor="#e2c59d" stopOpacity="0" />
                </radialGradient>
                <radialGradient id={`${id}-vignette`} cx="50%" cy="50%" r="75%">
                    <stop offset="0.55" stopColor="#000" stopOpacity="0" />
                    <stop offset="1" stopColor="#000" stopOpacity="0.5" />
                </radialGradient>
            </defs>
            <rect width="100%" height="100%" fill={`url(#${id}-base)`} />
            <rect width="100%" height="100%" filter={`url(#${id}-folds)`} opacity="0.34" style={{ mixBlendMode: "soft-light" }} />
            <rect width="100%" height="100%" fill={`url(#${id}-pool)`} />
            <rect width="100%" height="100%" fill={`url(#${id}-vignette)`} />
        </svg>
    );
}
