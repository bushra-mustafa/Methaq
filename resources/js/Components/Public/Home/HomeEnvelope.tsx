import { useImperativeHandle, useRef } from "react";
import type { CSSProperties, Ref } from "react";
import { gsap } from "gsap";
import { useGSAP } from "@gsap/react";
import type { EnvelopeTheme } from "./envelopeThemes";

gsap.registerPlugin(useGSAP);

export interface HomeEnvelopeHandle {
    open: () => Promise<void>;
    close: () => Promise<void>;
    jumpTo: (state: "open" | "closed") => void;
}

interface HomeEnvelopeProps {
    theme: EnvelopeTheme;
    sealDisabled: boolean;
    onSealClick: () => void;
    ref?: Ref<HomeEnvelopeHandle>;
}

/** Closing plays the opening timeline in reverse, slightly faster. */
const CLOSE_SPEED = 1.4;

const FLAP_PATH = "M0 0H400L206 196Q200 202 194 196Z";

function envelopeStyle({ colors }: EnvelopeTheme): CSSProperties {
    return {
        "--env-back": colors.back,
        "--env-sides": colors.sides,
        "--env-bottom": colors.bottom,
        "--env-flap": colors.flap,
        "--env-inner": colors.inner,
        "--env-shadow": colors.shadow,
    } as CSSProperties;
}

export default function HomeEnvelope({
    theme,
    sealDisabled,
    onSealClick,
    ref,
}: HomeEnvelopeProps) {
    const root = useRef<HTMLDivElement>(null);
    const timeline = useRef<gsap.core.Timeline | null>(null);
    const settle = useRef<(() => void) | null>(null);

    useGSAP(
        () => {
            const select = gsap.utils.selector(root);
            const shell = select('[data-part="shell"]');
            const seal = select('[data-part="seal"]');
            const flap = select('[data-part="flap"]');
            const letter = select('[data-part="letter"]');
            const shadow = select('[data-part="shadow"]');

            const finish = (): void => {
                settle.current?.();
                settle.current = null;
            };

            gsap.set(shell, { yPercent: -25 });
            gsap.set(flap, { rotationX: 0, zIndex: 40 });

            timeline.current = gsap
                .timeline({
                    paused: true,
                    onComplete: finish,
                    onReverseComplete: finish,
                })
                .to(seal, { scale: 1.08, duration: 0.1, ease: "power1.out" }, 0)
                .to(
                    seal,
                    {
                        scale: 0,
                        autoAlpha: 0,
                        duration: 0.2,
                        ease: "power2.in",
                    },
                    0.1,
                )
                .to(
                    flap,
                    { rotationX: -180, duration: 0.6, ease: "power2.inOut" },
                    0.15,
                )
                .set(flap, { zIndex: 15 }, 0.45)
                .to(
                    shadow,
                    {
                        scaleX: 0.9,
                        opacity: 0.6,
                        duration: 0.6,
                        ease: "power2.inOut",
                    },
                    0.15,
                )
                .to(
                    shell,
                    { yPercent: 0, duration: 0.75, ease: "power2.inOut" },
                    0.15,
                )
                .to(
                    letter,
                    { yPercent: -38, duration: 0.65, ease: "power3.out" },
                    0.6,
                );

            return () => {
                timeline.current = null;
            };
        },
        { scope: root },
    );

    useImperativeHandle(ref, () => {
        const run = (direction: "play" | "reverse"): Promise<void> =>
            new Promise((resolve) => {
                const sequence = timeline.current;
                const atTarget =
                    direction === "play"
                        ? sequence?.progress() === 1
                        : sequence?.progress() === 0;
                if (!sequence || atTarget) {
                    resolve();

                    return;
                }
                settle.current?.();
                settle.current = resolve;
                sequence.timeScale(direction === "play" ? 1 : CLOSE_SPEED);
                sequence[direction]();
            });

        return {
            open: () => run("play"),
            close: () => run("reverse"),
            jumpTo: (state) => {
                settle.current?.();
                settle.current = null;
                timeline.current?.progress(state === "open" ? 1 : 0).pause();
            },
        };
    }, []);

    return (
        <div
            ref={root}
            style={envelopeStyle(theme)}
            className="relative w-full pt-[34%]"
        >
            <div
                data-part="shell"
                className="relative aspect-[3/2] w-full [perspective:1400px]"
            >
                <div
                    data-part="shadow"
                    aria-hidden="true"
                    className="absolute inset-x-[8%] -bottom-[6%] z-0 h-[12%] rounded-[50%] bg-(--env-shadow) blur-lg"
                />

                <div
                    aria-hidden="true"
                    className="absolute inset-0 z-10 rounded-[4px] border border-methaq-gold/40 bg-(--env-back) shadow-methaq-raised transition-[background-color] duration-300"
                />

                <div
                    data-part="letter"
                    aria-hidden="true"
                    className="absolute inset-x-[6%] top-[6%] bottom-[8%] z-20 bg-methaq-cream p-[2.5%] shadow-[0_0_0_1px_#e4dccb,0_-6px_14px_rgba(4,48,31,0.18)]"
                >
                    <div className="flex h-full items-start justify-center gap-[5%] border border-methaq-gold/40 px-[6%] pt-[6%] text-right">
                        <img
                            src="/brand/logos/methaq-symbol-flat.svg"
                            alt=""
                            width={432}
                            height={306}
                            className="h-auto w-[17%] shrink-0"
                        />
                        <div className="flex min-w-0 flex-col">
                            <span className="text-[clamp(10px,2.2vw,12px)] text-methaq-gold-dark">
                                {theme.letter.occasion}
                            </span>
                            <span className="font-methaq-display text-[clamp(18px,4.6vw,28px)] leading-[1.35] text-methaq-primary">
                                {theme.letter.names}
                            </span>
                            <span className="text-[clamp(10px,2.2vw,12px)] text-methaq-muted">
                                {theme.letter.detail}
                            </span>
                        </div>
                    </div>
                </div>

                <svg
                    aria-hidden="true"
                    className="absolute inset-0 z-30 size-full rounded-[4px] drop-shadow-[0_-1px_2px_rgba(0,0,0,0.18)]"
                    viewBox="0 0 400 400"
                    preserveAspectRatio="none"
                >
                    <path
                        d="M0 0L200 188L400 0V400H0Z"
                        className="fill-(--env-sides) transition-[fill] duration-300"
                    />
                    <path
                        d="M0 400L200 190L400 400Z"
                        className="fill-(--env-bottom) transition-[fill] duration-300"
                    />
                    <path
                        d="M0 400L200 190L400 400M0 0L170 190M400 0L230 190"
                        fill="none"
                        stroke="#b69a50"
                        strokeOpacity="0.45"
                        strokeWidth="1.5"
                    />
                </svg>

                <div
                    data-part="flap"
                    aria-hidden="true"
                    className="absolute inset-0 origin-top [transform-style:preserve-3d]"
                >
                    <svg
                        className="absolute inset-0 size-full drop-shadow-[0_4px_4px_rgba(0,0,0,0.22)] [backface-visibility:hidden]"
                        viewBox="0 0 400 400"
                        preserveAspectRatio="none"
                    >
                        <path
                            d={FLAP_PATH}
                            className="fill-(--env-flap) transition-[fill] duration-300"
                            stroke="#b69a50"
                            strokeOpacity="0.5"
                        />
                        <path
                            d="M18 8L200 178L382 8"
                            fill="none"
                            stroke="#e6d28b"
                            strokeOpacity="0.55"
                        />
                    </svg>
                    <svg
                        className="absolute inset-0 size-full [backface-visibility:hidden] [transform:rotateY(180deg)]"
                        viewBox="0 0 400 400"
                        preserveAspectRatio="none"
                    >
                        <path
                            d={FLAP_PATH}
                            className="fill-(--env-inner) transition-[fill] duration-300"
                            stroke="#b69a50"
                            strokeOpacity="0.4"
                        />
                    </svg>
                </div>

                <div
                    data-part="seal"
                    className="absolute left-1/2 top-1/2 z-50 w-[16%] -translate-x-1/2 -translate-y-1/2"
                >
                    <button
                        type="button"
                        onClick={onSealClick}
                        disabled={sealDisabled}
                        aria-label={`افتحي الظرف ال${theme.colorName}`}
                        className="grid aspect-square w-full cursor-pointer touch-manipulation place-items-center rounded-full border border-methaq-gold-light/70 bg-[radial-gradient(circle_at_35%_30%,#dcc47c,#b69a50_55%,#8b6a2c)] transition-[scale] duration-300 animate-methaq-seal-pulse hover:scale-105 motion-reduce:animate-none disabled:cursor-default"
                    >
                        <img
                            src="/brand/logos/methaq-symbol-small-white.svg"
                            alt=""
                            width={64}
                            height={64}
                            className="h-auto w-[58%]"
                        />
                    </button>
                </div>
            </div>
        </div>
    );
}
