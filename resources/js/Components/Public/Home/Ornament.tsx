import type { SVGProps } from "react";

type OrnamentProps = SVGProps<SVGSVGElement>;

/**
 * Eight-pointed star (two squares turned 45°) inside a ring: the geometric
 * Arabic rosette. Hairline strokes in currentColor, purely decorative.
 */
export function EightPointStar(props: OrnamentProps) {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 200 200"
            fill="none"
            stroke="currentColor"
            strokeWidth={1}
            strokeLinejoin="round"
            aria-hidden="true"
            focusable="false"
            {...props}
        >
            <circle cx="100" cy="100" r="96" vectorEffect="non-scaling-stroke" />
            <path
                d="M182 100 100 182 18 100 100 18ZM158 42v116H42V42Z"
                vectorEffect="non-scaling-stroke"
            />
            <path
                d="M148 119.9 119.9 148H80.1L52 119.9V80.1L80.1 52h39.8L148 80.1Z"
                vectorEffect="non-scaling-stroke"
            />
            <circle cx="100" cy="100" r="9" vectorEffect="non-scaling-stroke" />
        </svg>
    );
}

/** A thin rule with a small star at its centre, used between large blocks. */
export function StarDivider({ className = "" }: { className?: string }) {
    return (
        <div
            aria-hidden="true"
            className={`flex items-center gap-4 text-methaq-gold ${className}`}
        >
            <span className="h-px flex-1 bg-gradient-to-l from-current/50 to-transparent" />
            <EightPointStar className="size-6 shrink-0" />
            <span className="h-px flex-1 bg-gradient-to-r from-current/50 to-transparent" />
        </div>
    );
}

/** Double-line frame corner; mirror it with scale utilities for the other corners. */
export function CornerFlourish(props: OrnamentProps) {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 48 48"
            fill="none"
            stroke="currentColor"
            strokeWidth={1}
            strokeLinecap="round"
            aria-hidden="true"
            focusable="false"
            {...props}
        >
            <path d="M1 47V17A16 16 0 0 1 17 1h30" vectorEffect="non-scaling-stroke" />
            <path d="M9 47V21A12 12 0 0 1 21 9h26" vectorEffect="non-scaling-stroke" />
            <path d="m14 14 3.5 3.5L14 21l-3.5-3.5Z" vectorEffect="non-scaling-stroke" />
        </svg>
    );
}
