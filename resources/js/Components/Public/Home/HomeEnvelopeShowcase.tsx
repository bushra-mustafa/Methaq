import { useEffect, useRef, useState } from "react";
import type { CSSProperties, RefObject } from "react";
import { Pause, Play, RotateCcw } from "lucide-react";
import {
    EnvelopeLetter,
    InvitationEnvelope,
} from "../../../Domains/Editor/Components/InvitationEnvelope";
import { emeraldTheme, envelopeThemes } from "./envelopeThemes";
import type { EnvelopeTheme } from "./envelopeThemes";

/** One full cycle per envelope lasts about 4.8 seconds. */
const OPENING_MS = 1400;
const SEALED_HOLD_MS = 600;
const OPENED_HOLD_MS = 1100;
const SWAP_MS = 300;

/**
 * closed → open → closing → swapping (fade to the next envelope) → closed.
 * The envelope itself only knows "closed" or "open"; CSS transitions do the motion.
 */
type ShowcasePhase = "closed" | "open" | "closing" | "swapping";

function usePrefersReducedMotion(): boolean {
    const [reduced, setReduced] = useState(
        () => window.matchMedia("(prefers-reduced-motion: reduce)").matches,
    );

    useEffect(() => {
        const media = window.matchMedia("(prefers-reduced-motion: reduce)");
        const update = (): void => setReduced(media.matches);
        media.addEventListener("change", update);

        return () => media.removeEventListener("change", update);
    }, []);

    return reduced;
}

function useVisibleOnScreen(element: RefObject<HTMLElement | null>): boolean {
    const [inView, setInView] = useState(false);
    const [pageVisible, setPageVisible] = useState(true);

    useEffect(() => {
        const target = element.current;
        if (!target) {
            return;
        }
        const observer = new IntersectionObserver(
            ([entry]) => setInView(entry?.isIntersecting ?? false),
            { threshold: 0.35 },
        );
        observer.observe(target);
        const onVisibility = (): void =>
            setPageVisible(document.visibilityState === "visible");
        document.addEventListener("visibilitychange", onVisibility);

        return () => {
            observer.disconnect();
            document.removeEventListener("visibilitychange", onVisibility);
        };
    }, [element]);

    return inView && pageVisible;
}

function nextTheme(current: EnvelopeTheme): EnvelopeTheme {
    const index = envelopeThemes.findIndex((theme) => theme.id === current.id);

    return envelopeThemes[(index + 1) % envelopeThemes.length] ?? emeraldTheme;
}

interface HomeEnvelopeShowcaseProps {
    mode: "auto" | "manual";
    initialTheme?: EnvelopeTheme;
}

export default function HomeEnvelopeShowcase({
    mode,
    initialTheme = emeraldTheme,
}: HomeEnvelopeShowcaseProps) {
    const container = useRef<HTMLDivElement>(null);
    const pendingTheme = useRef<EnvelopeTheme | null>(null);
    const reducedMotion = usePrefersReducedMotion();
    const visible = useVisibleOnScreen(container);

    const [theme, setTheme] = useState<EnvelopeTheme>(initialTheme);
    const [phase, setPhase] = useState<ShowcasePhase>("closed");
    const [autoplay, setAutoplay] = useState(mode === "auto");
    const [announcement, setAnnouncement] = useState("");

    const isRunning = autoplay && visible && !reducedMotion;

    useEffect(() => {
        if (reducedMotion) {
            setPhase("open");
        }
    }, [reducedMotion]);

    useEffect(() => {
        const step: Partial<Record<ShowcasePhase, [number, () => void]>> = {
            closing: [
                OPENING_MS,
                () =>
                    setPhase(
                        pendingTheme.current || isRunning
                            ? "swapping"
                            : "closed",
                    ),
            ],
            swapping: [
                SWAP_MS,
                () => {
                    const chosen = pendingTheme.current;
                    pendingTheme.current = null;
                    setTheme((current) => chosen ?? nextTheme(current));
                    setPhase("closed");
                },
            ],
        };
        if (isRunning) {
            step.closed = [SEALED_HOLD_MS, () => setPhase("open")];
            step.open = [OPENING_MS + OPENED_HOLD_MS, () => setPhase("closing")];
        }
        const next = step[phase];
        if (!next) {
            return;
        }
        const timer = window.setTimeout(next[1], next[0]);

        return () => window.clearTimeout(timer);
    }, [phase, isRunning]);

    const openManually = (): void => {
        if (phase === "swapping") {
            return;
        }
        setAutoplay(false);
        setAnnouncement(`الظرف ال${theme.colorName}، مفتوح`);
        setPhase("open");
    };

    const closeManually = (): void => {
        setAutoplay(false);
        setAnnouncement(`الظرف ال${theme.colorName}، مغلق`);
        setPhase("closing");
    };

    const chooseTheme = (selected: EnvelopeTheme): void => {
        setAutoplay(false);
        setAnnouncement(`الظرف ال${selected.colorName}`);
        if (reducedMotion) {
            setTheme(selected);

            return;
        }
        pendingTheme.current = selected;
        setPhase(phase === "open" ? "closing" : "swapping");
    };

    const toggleAutoplay = (): void => {
        setAnnouncement(autoplay ? "توقف العرض" : "يعمل العرض");
        setAutoplay((value) => !value);
    };

    return (
        <div
            ref={container}
            className="mx-auto flex w-full max-w-[440px] flex-col items-center gap-7"
        >
            <div
                className={`relative w-full pt-[18%] transition-opacity duration-300 ${phase === "swapping" ? "opacity-0" : "opacity-100"}`}
                style={{ "--opening-duration": `${OPENING_MS}ms` } as CSSProperties}
            >
                <InvitationEnvelope
                    colors={theme.colors}
                    appearance={theme.appearance}
                    state={phase === "open" ? "open" : "closed"}
                    letter={
                        <EnvelopeLetter
                            names={theme.letter.names}
                            detail={theme.letter.detail}
                        />
                    }
                    sealLabel={theme.letter.names}
                    onSealClick={openManually}
                />
                {!autoplay && phase === "open" && !reducedMotion && (
                    <button
                        type="button"
                        onClick={closeManually}
                        aria-label="أغلقي الظرف"
                        title="أغلقي الظرف"
                        className="absolute end-0 top-0 grid size-10 cursor-pointer touch-manipulation place-items-center rounded-full border border-methaq-border bg-methaq-surface text-methaq-primary shadow-methaq-card transition-colors hover:bg-methaq-sage"
                    >
                        <RotateCcw size={17} aria-hidden="true" />
                    </button>
                )}
            </div>

            <div className="flex flex-wrap items-center justify-center gap-2.5">
                {mode === "manual" && (
                    <div
                        role="group"
                        aria-label="لون الظرف"
                        className="flex flex-wrap justify-center gap-2.5"
                    >
                        {envelopeThemes.map((option) => (
                            <button
                                key={option.id}
                                type="button"
                                onClick={() => chooseTheme(option)}
                                aria-pressed={theme.id === option.id}
                                className="inline-flex min-h-10 cursor-pointer touch-manipulation items-center gap-2 rounded-full border border-methaq-border bg-methaq-surface px-3.5 text-[14px] text-methaq-primary transition-colors hover:bg-methaq-sage aria-pressed:border-methaq-gold aria-pressed:bg-methaq-sage"
                            >
                                <span
                                    aria-hidden="true"
                                    className="size-3.5 rounded-full border border-methaq-gold/60"
                                    style={{
                                        backgroundColor: option.colors.paper,
                                    }}
                                />
                                {option.colorName}
                            </button>
                        ))}
                    </div>
                )}

                {mode === "auto" && !reducedMotion && (
                    <button
                        type="button"
                        onClick={toggleAutoplay}
                        aria-pressed={!autoplay}
                        aria-label={autoplay ? "أوقفي العرض" : "شغّلي العرض"}
                        title={autoplay ? "أوقفي العرض" : "شغّلي العرض"}
                        className="grid size-10 cursor-pointer touch-manipulation place-items-center rounded-full border border-methaq-border bg-methaq-surface text-methaq-primary transition-colors hover:bg-methaq-sage"
                    >
                        {autoplay ? (
                            <Pause size={16} aria-hidden="true" />
                        ) : (
                            <Play size={16} aria-hidden="true" />
                        )}
                    </button>
                )}
            </div>

            <span className="sr-only" aria-live="polite">
                {announcement}
            </span>
        </div>
    );
}
