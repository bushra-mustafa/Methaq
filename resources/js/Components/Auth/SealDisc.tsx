import { useId } from "react";

/** The wavy outline of a wax seal: 14 soft lobes around a circle, in a 100-unit box. */
const SEAL_EDGE = (() => {
    const points: string[] = [];
    for (let degrees = 0; degrees <= 360; degrees += 3) {
        const angle = (degrees * Math.PI) / 180;
        const radius = 47 + 1.6 * Math.sin(angle * 14);
        points.push(`${(50 + radius * Math.cos(angle)).toFixed(2)},${(50 + radius * Math.sin(angle)).toFixed(2)}`);
    }

    return `M${points.join("L")}Z`;
})();

interface SealDiscProps {
    /** Centre of the seal in the parent SVG's coordinates. */
    x: number;
    y: number;
    size: number;
}

/** A gold wax seal pressed with Methaq's eight-pointed star; drawn inside another SVG. */
export default function SealDisc({ x, y, size }: SealDiscProps) {
    const gradient = useId().replace(/:/g, "");
    const half = size / 2;

    return (
        <g transform={`translate(${x - half} ${y - half}) scale(${size / 100})`}>
            <defs>
                <radialGradient id={gradient} cx="34%" cy="28%" r="85%">
                    <stop offset="0" stopColor="#f3e0ba" />
                    <stop offset="0.5" stopColor="#d3ad6d" />
                    <stop offset="1" stopColor="#97763f" />
                </radialGradient>
            </defs>
            <path d={SEAL_EDGE} fill={`url(#${gradient})`} />
            <circle cx="50" cy="50" r="36" fill="none" stroke="#6b5129" strokeOpacity="0.4" strokeWidth="1" />
            <circle cx="50" cy="50" r="33.5" fill="none" stroke="#fff6e2" strokeOpacity="0.35" strokeWidth="0.6" />
            <g fill="none" stroke="#6b5129" strokeOpacity="0.62" strokeWidth="1.3" strokeLinejoin="round">
                <path d="M50 24 76 50 50 76 24 50ZM33 33H67V67H33Z" />
                <circle cx="50" cy="50" r="4" />
            </g>
        </g>
    );
}
