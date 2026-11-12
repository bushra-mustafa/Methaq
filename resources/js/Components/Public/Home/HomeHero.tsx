import { Link } from "@inertiajs/react";
import HomeEnvelopeShowcase from "./HomeEnvelopeShowcase";

/** Gold dust from the Stitch hero: position, size and delay of each speck. */
const dust = [
    { className: "top-20 right-1/3 size-2.5", delay: "0s" },
    { className: "top-1/2 right-1/5 size-2", delay: "2s" },
    { className: "bottom-24 right-1/2 size-3", delay: "3.5s" },
    { className: "top-28 left-1/4 size-2", delay: "1.2s" },
];

interface HomeHeroProps {
    isAuthenticated: boolean;
    showPrototype: boolean;
}

export default function HomeHero({
    isAuthenticated,
    showPrototype,
}: HomeHeroProps) {
    return (
        <section
            aria-labelledby="home-hero-title"
            className="relative overflow-hidden px-6 py-16 lg:px-12 lg:py-24"
        >
            <div
                aria-hidden="true"
                className="pointer-events-none absolute -top-32 right-1/4 size-96 rounded-full bg-methaq-gold-light/20 blur-3xl"
            />
            <div
                aria-hidden="true"
                className="pointer-events-none absolute top-1/2 -left-20 size-80 rounded-full bg-methaq-sage blur-3xl"
            />
            {dust.map((speck) => (
                <span
                    key={speck.className}
                    aria-hidden="true"
                    className={`pointer-events-none absolute rounded-full bg-[radial-gradient(circle,rgba(230,210,139,0.95)_0%,rgba(182,154,80,0.35)_60%,transparent_100%)] opacity-40 motion-safe:animate-methaq-dust ${speck.className}`}
                    style={{ animationDelay: speck.delay }}
                />
            ))}
            <div className="relative mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-2 lg:gap-10">
                <div className="flex min-w-0 flex-col items-start gap-6 text-right motion-safe:animate-methaq-hero-in">
                    <h1
                        id="home-hero-title"
                        className="m-0 max-w-[14ch] font-methaq-display text-[40px] leading-[1.25] font-bold tracking-tight text-balance text-methaq-primary md:text-[50px] lg:text-[56px]"
                    >
                        لكل حكاية جميلة… دعوةٌ تليق بها
                    </h1>

                    <p className="m-0 max-w-[44ch] text-[17px] leading-[1.9] text-pretty text-methaq-muted">
                        صمّمي دعوتك، وشاركيها برابط واحد يفتحه ضيوفك كظرف
                        حقيقي، فتنكشف بطاقتك على موسيقاك.
                    </p>

                    <div className="flex flex-wrap items-center gap-x-6 gap-y-3 pt-1">
                        <Link
                            href={isAuthenticated ? "/app" : "/register"}
                            className="inline-flex min-h-12 touch-manipulation items-center justify-center rounded-methaq-button bg-methaq-primary px-7 text-[15px] font-semibold text-white shadow-methaq-soft transition-[background-color,translate] duration-200 hover:-translate-y-0.5 hover:bg-methaq-primary-hover motion-reduce:hover:translate-y-0"
                        >
                            {isAuthenticated ? "افتحي مناسباتك" : "صمّمي دعوتك"}
                        </Link>
                        <a
                            href="#home-envelope-title"
                            className="text-[15px] font-medium text-methaq-primary underline decoration-methaq-gold/60 underline-offset-8 transition-[text-decoration-color] hover:decoration-methaq-gold"
                        >
                            جرّبي فتح الظرف
                        </a>
                        {showPrototype && (
                            <a
                                href="/dev/design-lab"
                                className="text-[14px] text-methaq-muted underline underline-offset-8"
                            >
                                البطاقة التجريبية
                            </a>
                        )}
                    </div>
                </div>

                <div className="min-w-0">
                    <HomeEnvelopeShowcase mode="auto" />
                </div>
            </div>
        </section>
    );
}
