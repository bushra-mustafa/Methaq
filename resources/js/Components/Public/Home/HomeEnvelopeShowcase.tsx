import { useCallback, useEffect, useRef, useState } from "react";
import { gsap } from "gsap";
import { Pause, Play, RotateCcw } from "lucide-react";
import HomeEnvelope from "./HomeEnvelope";
import type { HomeEnvelopeHandle } from "./HomeEnvelope";
import { emeraldTheme, envelopeThemes } from "./envelopeThemes";
import type { EnvelopeTheme } from "./envelopeThemes";

/** One full cycle per colour lasts about 4.5 seconds. */
const SEALED_HOLD_SECONDS = 0.6;
const OPENED_HOLD_SECONDS = 1.4;
const THEME_SWAP_SECONDS = 0.35;

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
    const envelope = useRef<HomeEnvelopeHandle>(null);
    const container = useRef<HTMLDivElement>(null);
    const pendingCall = useRef<gsap.core.Tween | null>(null);
    const runId = useRef(0);
    const animationToken = useRef(0);
    const reducedMotion = usePrefersReducedMotion();

    const [theme, setTheme] = useState<EnvelopeTheme>(initialTheme);
    const [isOpen, setIsOpen] = useState(false);
    const [isAnimating, setIsAnimating] = useState(false);
    const [autoplay, setAutoplay] = useState(mode === "auto");
    const [inView, setInView] = useState(false);
    const [pageVisible, setPageVisible] = useState(true);
    const [announcement, setAnnouncement] = useState("");

    const themeRef = useRef(theme);
    themeRef.current = theme;
    const isOpenRef = useRef(isOpen);
    isOpenRef.current = isOpen;

    const isRunning = autoplay && inView && pageVisible && !reducedMotion;

    const stopLoop = useCallback((): void => {
        runId.current += 1;
        pendingCall.current?.kill();
        pendingCall.current = null;
    }, []);

    const animate = useCallback(
        async (direction: "open" | "close"): Promise<void> => {
            const handle = envelope.current;
            if (!handle) {
                return;
            }
            const token = ++animationToken.current;
            setIsAnimating(true);
            await (direction === "open" ? handle.open() : handle.close());
            if (token !== animationToken.current) {
                return;
            }
            setIsOpen(direction === "open");
            setIsAnimating(false);
        },
        [],
    );

    useEffect(() => {
        const element = container.current;
        if (!element) {
            return;
        }
        const observer = new IntersectionObserver(
            ([entry]) => setInView(entry?.isIntersecting ?? false),
            { threshold: 0.35 },
        );
        observer.observe(element);
        const onVisibility = (): void =>
            setPageVisible(document.visibilityState === "visible");
        document.addEventListener("visibilitychange", onVisibility);

        return () => {
            observer.disconnect();
            document.removeEventListener("visibilitychange", onVisibility);
        };
    }, []);

    useEffect(() => {
        if (reducedMotion) {
            stopLoop();
            envelope.current?.jumpTo("open");
            setIsOpen(true);
            setIsAnimating(false);
        }
    }, [reducedMotion, stopLoop]);

    useEffect(() => {
        if (!isRunning) {
            stopLoop();

            return;
        }

        const id = ++runId.current;
        const alive = (): boolean => runId.current === id;
        const wait = (seconds: number, next: () => void): void => {
            pendingCall.current = gsap.delayedCall(seconds, () => {
                if (alive()) {
                    next();
                }
            });
        };

        const cycleFromClosed = (): void => {
            wait(SEALED_HOLD_SECONDS, () => {
                void animate("open").then(() => {
                    if (!alive()) {
                        return;
                    }
                    wait(OPENED_HOLD_SECONDS, () => {
                        void animate("close").then(() => {
                            if (!alive()) {
                                return;
                            }
                            setTheme(nextTheme(themeRef.current));
                            wait(THEME_SWAP_SECONDS, cycleFromClosed);
                        });
                    });
                });
            });
        };

        if (isOpenRef.current) {
            wait(OPENED_HOLD_SECONDS, () => {
                void animate("close").then(() => {
                    if (alive()) {
                        setTheme(nextTheme(themeRef.current));
                        wait(THEME_SWAP_SECONDS, cycleFromClosed);
                    }
                });
            });
        } else {
            cycleFromClosed();
        }

        return stopLoop;
    }, [isRunning, animate, stopLoop]);

    const takeControl = (): void => {
        setAutoplay(false);
        stopLoop();
    };

    const openManually = (): void => {
        takeControl();
        setAnnouncement(`الظرف ال${theme.colorName}، مفتوح`);
        void animate("open");
    };

    const closeManually = (): void => {
        takeControl();
        setAnnouncement(`الظرف ال${theme.colorName}، مغلق`);
        void animate("close");
    };

    const chooseTheme = (selected: EnvelopeTheme): void => {
        takeControl();
        setAnnouncement(`الظرف ال${selected.colorName}`);
        if (reducedMotion || (!isOpen && !isAnimating)) {
            setTheme(selected);

            return;
        }
        void animate("close").then(() => setTheme(selected));
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
            <div className="relative w-full">
                <HomeEnvelope
                    ref={envelope}
                    theme={theme}
                    sealDisabled={isOpen || isAnimating}
                    onSealClick={openManually}
                />
                {!autoplay && isOpen && !isAnimating && !reducedMotion && (
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
                                        backgroundColor: option.colors.bottom,
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
