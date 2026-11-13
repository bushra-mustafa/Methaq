import { Link } from "@inertiajs/react";
import HomeEnvelopeShowcase from "./HomeEnvelopeShowcase";
import { EightPointStar } from "./Ornament";

interface HomeHeroProps {
    isAuthenticated: boolean;
    showPrototype: boolean;
}

/**
 * Opening: the headline sets the tone in large Amiri, and the envelope the guests
 * will open sits beside it on a faint geometric rosette. The only motion is one
 * staggered entrance of the headline lines.
 */
export default function HomeHero({
    isAuthenticated,
    showPrototype,
}: HomeHeroProps) {
    return (
        <section
            aria-labelledby="home-hero-title"
            className="relative isolate overflow-hidden px-6 py-methaq-section lg:px-12"
        >
            <EightPointStar
                className="pointer-events-none absolute -z-10 hidden size-[40rem] max-w-none -translate-y-1/2 text-methaq-gold/30 lg:block"
                style={{ insetInlineEnd: "-5rem", top: "50%" }}
            />

            <div className="mx-auto grid max-w-7xl items-center gap-14 lg:grid-cols-12 lg:gap-8">
                <div className="flex min-w-0 flex-col items-start gap-8 lg:col-span-7">
                    <h1
                        id="home-hero-title"
                        className="m-0 font-methaq-display text-methaq-display font-bold tracking-[-0.01em] text-balance text-methaq-primary"
                    >
                        <span
                            className="block motion-safe:animate-methaq-hero-in"
                            style={{ animationDelay: "0ms" }}
                        >
                            لكل حكاية جميلة…
                        </span>
                        <span
                            className="block motion-safe:animate-methaq-hero-in"
                            style={{ animationDelay: "140ms" }}
                        >
                            دعوةٌ تليق بها
                        </span>
                    </h1>

                    <p
                        className="m-0 max-w-[38ch] text-methaq-lead text-pretty text-methaq-muted motion-safe:animate-methaq-hero-in"
                        style={{ animationDelay: "280ms" }}
                    >
                        صمّمي دعوتك، وشاركيها برابط واحد يفتحه ضيوفك كظرف
                        حقيقي، فتنكشف بطاقتك على موسيقاك.
                    </p>

                    <div
                        className="flex flex-wrap items-center gap-x-8 gap-y-4 motion-safe:animate-methaq-hero-in"
                        style={{ animationDelay: "400ms" }}
                    >
                        <Link
                            href={isAuthenticated ? "/app" : "/register"}
                            className="inline-flex min-h-13 touch-manipulation items-center justify-center rounded-methaq-button bg-methaq-primary px-8 text-[16px] font-semibold text-white shadow-methaq-soft transition-[background-color,scale] duration-200 hover:bg-methaq-primary-hover active:scale-[0.96] motion-reduce:active:scale-100"
                        >
                            {isAuthenticated ? "افتحي مناسباتك" : "صمّمي دعوتك"}
                        </Link>
                        <a
                            href="#home-envelope-title"
                            className="inline-flex min-h-11 items-center text-[16px] font-medium text-methaq-primary underline decoration-methaq-gold/60 decoration-1 underline-offset-8 transition-[text-decoration-color] hover:decoration-methaq-gold"
                        >
                            جرّبي فتح الظرف
                        </a>
                        {showPrototype && (
                            <a
                                href="/dev/design-lab"
                                className="inline-flex min-h-11 items-center text-[14px] text-methaq-muted underline underline-offset-8"
                            >
                                البطاقة التجريبية
                            </a>
                        )}
                    </div>
                </div>

                <div className="min-w-0 lg:col-span-5">
                    <HomeEnvelopeShowcase mode="auto" />
                </div>
            </div>
        </section>
    );
}
