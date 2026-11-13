import { useId } from "react";
import type { PropsWithChildren } from "react";

/** A wavy-edged disc, like wax pressed under a signet: 14 soft lobes around a circle. */
const SEAL_EDGE = (() => {
    const points: string[] = [];
    for (let degrees = 0; degrees <= 360; degrees += 3) {
        const angle = (degrees * Math.PI) / 180;
        const radius = 47 + 1.6 * Math.sin(angle * 14);
        points.push(`${(50 + radius * Math.cos(angle)).toFixed(2)},${(50 + radius * Math.sin(angle)).toFixed(2)}`);
    }

    return `M${points.join("L")}Z`;
})();

/** The gold wax disc and its label; sized by the parent, decorative apart from the label. */
export default function WaxSeal({ children }: PropsWithChildren) {
    const gradient = useId().replace(/:/g, "");

    return (
        <span className="relative grid size-full place-items-center">
            <svg
                viewBox="0 0 100 100"
                aria-hidden="true"
                focusable="false"
                className="absolute inset-0 size-full drop-shadow-[0_10px_14px_rgba(0,0,0,0.38)]"
            >
                <defs>
                    <radialGradient id={gradient} cx="34%" cy="28%" r="85%">
                        <stop offset="0" stopColor="#f3e0ba" />
                        <stop offset="0.5" stopColor="#d3ad6d" />
                        <stop offset="1" stopColor="#97763f" />
                    </radialGradient>
                </defs>
                <path d={SEAL_EDGE} fill={`url(#${gradient})`} />
                <circle cx="50" cy="50" r="36" fill="none" stroke="#6b5129" strokeOpacity="0.42" strokeWidth="1" />
                <circle cx="50" cy="50" r="33.5" fill="none" stroke="#fff6e2" strokeOpacity="0.35" strokeWidth="0.6" />
            </svg>
            <span className="relative font-methaq-display text-[1.55rem] leading-none font-bold text-[#33260f]">
                {children}
            </span>
        </span>
    );
}
