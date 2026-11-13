import { useId } from "react";
import type { PropsWithChildren } from "react";

/**
 * A pale marble slab drawn in SVG (turbulence veins over a soft stone gradient),
 * the table the invitations rest on. No images, nothing to download.
 */
export default function PaperSurface({
    children,
    className = "",
}: PropsWithChildren<{ className?: string }>) {
    const id = useId().replace(/:/g, "");

    return (
        <div
            className={`relative isolate overflow-hidden rounded-[1.25rem] border border-methaq-border bg-methaq-sage ${className}`}
        >
            <svg
                aria-hidden="true"
                focusable="false"
                className="pointer-events-none absolute inset-0 -z-10 size-full"
                preserveAspectRatio="none"
            >
                <defs>
                    <linearGradient id={`${id}-stone`} x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stopColor="#f6fbf2" />
                        <stop offset="0.55" stopColor="#e8eee3" />
                        <stop offset="1" stopColor="#dde4d8" />
                    </linearGradient>
                    <filter id={`${id}-veins`} x="0" y="0" width="100%" height="100%">
                        <feTurbulence
                            type="turbulence"
                            baseFrequency="0.0045 0.013"
                            numOctaves="4"
                            seed="11"
                        />
                        <feColorMatrix
                            type="matrix"
                            values="0 0 0 0 0.48  0 0 0 0 0.38  0 0 0 0 0.23  -7 0 0 0 1.55"
                        />
                    </filter>
                    <filter id={`${id}-haze`} x="0" y="0" width="100%" height="100%">
                        <feTurbulence
                            type="fractalNoise"
                            baseFrequency="0.006 0.02"
                            numOctaves="3"
                            seed="4"
                        />
                        <feColorMatrix
                            type="matrix"
                            values="0 0 0 0 1  0 0 0 0 1  0 0 0 0 1  0 0 0 0.9 -0.3"
                        />
                    </filter>
                </defs>
                <rect width="100%" height="100%" fill={`url(#${id}-stone)`} />
                <rect width="100%" height="100%" filter={`url(#${id}-haze)`} opacity="0.55" />
                <rect width="100%" height="100%" filter={`url(#${id}-veins)`} opacity="0.2" />
            </svg>
            {children}
        </div>
    );
}
