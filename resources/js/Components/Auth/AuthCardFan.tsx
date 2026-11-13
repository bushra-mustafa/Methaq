import type { CSSProperties } from "react";
import { EightPointStar } from "../Public/Home/Ornament";

interface Sketch {
    occasion: string;
    names: string;
    detail: string;
    paper: string;
    ink: string;
    accent: string;
    x: string;
    y: string;
    rotate: string;
    delay: number;
}

/** Sample invitations in three palettes; drawn here, not photographs of real events. */
const sketches: Sketch[] = [
    { occasion: "حنّة", names: "مريم", detail: "ليلة الخميس", paper: "#5c1f2f", ink: "#f6ece0", accent: "#e2c59d", x: "-9.5rem", y: "1.25rem", rotate: "-9deg", delay: 0 },
    { occasion: "تخرّج", names: "ريم", detail: "حفل التخرج", paper: "#0f3b2e", ink: "#f6fbf2", accent: "#e2c59d", x: "9.5rem", y: "1.25rem", rotate: "9deg", delay: 120 },
    { occasion: "زفاف", names: "سارة وأحمد", detail: "الجمعة، السابعة مساءً", paper: "#f7f1e3", ink: "#203322", accent: "#7a613b", x: "0rem", y: "-1rem", rotate: "0deg", delay: 240 },
];

function SketchCard({ sketch }: { sketch: Sketch }) {
    const pose = { "--fan-x": sketch.x, "--fan-y": sketch.y, "--fan-r": sketch.rotate, animationDelay: `${sketch.delay}ms`, backgroundColor: sketch.paper, color: sketch.ink } as CSSProperties;

    return (
        <div
            className="absolute start-1/2 top-0 -ms-[5.5rem] aspect-[9/16] w-44 shadow-[0_24px_40px_-14px_rgba(0,0,0,0.55)] outline-1 -outline-offset-1 outline-white/10 motion-safe:animate-methaq-fan motion-reduce:[transform:translate(var(--fan-x),var(--fan-y))_rotate(var(--fan-r))]"
            style={pose}
        >
            <div
                className="flex size-full flex-col items-center justify-center gap-3 p-4 text-center motion-safe:animate-methaq-float"
                style={{ animationDelay: `${sketch.delay * 6}ms` }}
            >
                <span className="pointer-events-none absolute inset-2.5 border" style={{ borderColor: sketch.accent, opacity: 0.55 }} />
                <EightPointStar className="size-6" style={{ color: sketch.accent }} />
                <span className="text-[13px]" style={{ color: sketch.accent }}>{sketch.occasion}</span>
                <span className="font-methaq-display text-[26px] leading-[1.25] font-bold">{sketch.names}</span>
                <span className="h-px w-10" style={{ backgroundColor: sketch.accent }} />
                <span className="text-[12px] opacity-85">{sketch.detail}</span>
            </div>
        </div>
    );
}

/** Three invitations fanned from one stack: the variety a visitor can start from. */
export default function AuthCardFan() {
    return (
        <div aria-hidden="true" className="relative mx-auto h-[24rem] w-full max-w-md">
            {sketches.map((sketch) => <SketchCard key={sketch.occasion} sketch={sketch} />)}
        </div>
    );
}
