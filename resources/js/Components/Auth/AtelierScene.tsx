import { useId } from "react";
import SealDisc from "./SealDisc";

/**
 * A stationery flat-lay: two of Methaq's own invitations resting behind a closed ivory
 * envelope sealed in gold. Everything is positioned in percentages of the box so the
 * same scene works at 20rem on a phone and 34rem on a desktop.
 */
export default function AtelierScene({ className = "" }: { className?: string }) {
    const id = useId().replace(/:/g, "");

    return (
        <div aria-hidden="true" className={`relative aspect-[6/5] w-full ${className}`}>
            <img
                src="/editor/previews/templates/burgundy-noir.png"
                alt=""
                width={540}
                height={960}
                className="absolute start-[40%] top-[1%] w-[34%] rotate-[9deg] bg-methaq-primary shadow-[0_22px_34px_-14px_rgba(0,0,0,0.6)] outline-1 -outline-offset-1 outline-white/10 motion-safe:animate-methaq-hero-in"
                style={{ animationDelay: "420ms" }}
            />
            <img
                src="/editor/previews/templates/ivory-tale.png"
                alt=""
                width={540}
                height={960}
                className="absolute start-[12%] top-0 w-[38%] -rotate-[7deg] bg-methaq-cream shadow-[0_22px_34px_-14px_rgba(0,0,0,0.6)] outline-1 -outline-offset-1 outline-white/10 motion-safe:animate-methaq-hero-in"
                style={{ animationDelay: "260ms" }}
            />
            <svg
                viewBox="0 0 560 370"
                focusable="false"
                className="absolute start-[14%] bottom-[3%] w-[72%] rotate-[3deg] drop-shadow-[0_24px_28px_rgba(0,0,0,0.5)] motion-safe:animate-methaq-hero-in"
                style={{ animationDelay: "100ms" }}
            >
                <defs>
                    <linearGradient id={`${id}-paper`} x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stopColor="#f8f2e4" />
                        <stop offset="1" stopColor="#e4dac4" />
                    </linearGradient>
                    <linearGradient id={`${id}-side`} x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0" stopColor="#d9cdb2" />
                        <stop offset="1" stopColor="#efe6d2" />
                    </linearGradient>
                    <linearGradient id={`${id}-bottom`} x1="0" y1="1" x2="0" y2="0">
                        <stop offset="0" stopColor="#f3ebd9" />
                        <stop offset="1" stopColor="#e2d7bf" />
                    </linearGradient>
                    <linearGradient id={`${id}-flap`} x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stopColor="#f6efde" />
                        <stop offset="1" stopColor="#dccfb4" />
                    </linearGradient>
                    <filter id={`${id}-flap-shadow`} x="-10%" y="-10%" width="120%" height="140%">
                        <feDropShadow dx="0" dy="6" stdDeviation="6" floodColor="#3a2f18" floodOpacity="0.28" />
                    </filter>
                </defs>
                <rect width="560" height="370" rx="6" fill={`url(#${id}-paper)`} />
                <path d="M0 6 280 214 0 364Z" fill={`url(#${id}-side)`} />
                <path d="M560 6 280 214 560 364Z" fill={`url(#${id}-side)`} transform="matrix(1 0 0 1 0 0)" opacity="0.92" />
                <path d="M4 366 280 188 556 366Z" fill={`url(#${id}-bottom)`} />
                <g fill="none" stroke="#bfae88" strokeOpacity="0.55" strokeWidth="1.2">
                    <path d="M0 6 280 214 0 364M560 6 280 214 560 364M4 366 280 188 556 366" />
                </g>
                <path d="M2 2H558L280 236Z" fill={`url(#${id}-flap)`} filter={`url(#${id}-flap-shadow)`} />
                <path d="M2 2H558L280 236Z" fill="none" stroke="#bfae88" strokeOpacity="0.6" strokeWidth="1.2" strokeLinejoin="round" />
                <SealDisc x={280} y={226} size={96} />
            </svg>
        </div>
    );
}
