import { Link } from "@inertiajs/react";
import { ArrowLeft, Play, Sparkles } from "lucide-react";
import HomeInvitationDemo from "./HomeInvitationDemo";

type HomeHeroProps = {
    isAuthenticated: boolean;
    showPrototype: boolean;
};

export default function HomeHero({
    isAuthenticated,
    showPrototype,
}: HomeHeroProps) {
    return (
        <section
            className="relative isolate overflow-hidden bg-methaq-ivory py-12 sm:py-16 lg:py-20"
            aria-labelledby="home-hero-title"
        >
            {/* Soft background decorations */}
            <div
                aria-hidden="true"
                className="pointer-events-none absolute -right-28 -top-36 h-96 w-96 rounded-full bg-methaq-gold/10 blur-3xl"
            />
            <div
                aria-hidden="true"
                className="pointer-events-none absolute -bottom-36 -left-20 h-80 w-80 rounded-full bg-methaq-sage blur-3xl"
            />

            <div className="relative mx-auto max-w-7xl px-6 lg:px-8">
                <div className="grid min-w-0 items-center gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)] lg:gap-12">
                    {/* Right: Marketing content */}
                    <div className="flex min-w-0 flex-col items-start gap-6 text-right lg:pr-2">
                        <span className="inline-flex items-center gap-2 rounded-full border border-methaq-gold/30 bg-methaq-cream px-4 py-2 text-xs font-medium text-methaq-primary">
                            <Sparkles
                                size={15}
                                className="text-methaq-gold"
                                aria-hidden="true"
                            />
                            دعوات إلكترونية بتفاصيل تليق بفرحتك
                        </span>

                        <h1
                            id="home-hero-title"
                            className="font-methaq-display text-[40px] font-normal text-methaq-primary sm:text-[46px] lg:text-[50px]"
                        >
                            لكل حكاية جميلة...
                            <span className="mt-1 block text-methaq-gold">
                                دعوةٌ تليق بها
                            </span>
                        </h1>

                        <p className="max-w-[520px] text-[15px] leading-8 text-methaq-muted sm:text-base">
                            في ميثاق، نصنع مساحة لتصميم دعوات إلكترونية أنيقة،
                            تجمع بين جمال الخط العربي والتفاصيل الراقية وتجربة
                            العرض التفاعلية.
                        </p>

                        <div className="flex flex-wrap items-center gap-3">
                            <Link
                                href={isAuthenticated ? "/app" : "/register"}
                                className="inline-flex min-h-12 items-center justify-center gap-3 rounded-methaq-button bg-methaq-primary px-6 py-3 text-sm font-semibold text-white shadow-methaq-soft transition hover:-translate-y-0.5 hover:bg-methaq-primary-hover"
                            >
                                {isAuthenticated
                                    ? "مناسباتي"
                                    : "صمّمي دعوتك الآن"}
                                <ArrowLeft size={17} aria-hidden="true" />
                            </Link>

                            <a
                                href="#hero-envelope-preview"
                                className="inline-flex min-h-12 items-center justify-center gap-2 rounded-methaq-button border border-methaq-border bg-methaq-surface px-5 py-3 text-sm font-medium text-methaq-primary transition-colors hover:bg-methaq-sage"
                            >
                                <Play
                                    size={17}
                                    className="text-methaq-gold"
                                    aria-hidden="true"
                                />
                                شاهدي تجربة الظرف
                            </a>
                        </div>

                        <div className="flex flex-wrap items-center gap-5 text-sm">
                            <Link
                                href="/templates"
                                className="font-medium text-methaq-primary underline decoration-methaq-gold underline-offset-4"
                            >
                                استعرضي القوالب
                            </Link>

                            {showPrototype && (
                                <a
                                    href="/dev/design-lab"
                                    className="text-methaq-muted underline underline-offset-4"
                                >
                                    البطاقة التجريبية
                                </a>
                            )}
                        </div>

                        <div className="flex flex-wrap gap-2 pt-2">
                            {[
                                "تصاميم عربية أنيقة",
                                "تخصيص تفاصيل الدعوة",
                                "معاينة تفاعلية",
                            ].map((feature) => (
                                <span
                                    key={feature}
                                    className="rounded-full border border-methaq-border bg-methaq-sage/70 px-3 py-1.5 text-xs text-methaq-muted"
                                >
                                    {feature}
                                </span>
                            ))}
                        </div>
                    </div>

                    {/* Left: Existing invitation player */}
                    <div
                        id="hero-envelope-preview"
                        className="mx-auto w-full min-w-0 max-w-[470px] scroll-mt-24 lg:justify-self-end"
                    >
                        <HomeInvitationDemo
                            cardUrl="/editor/proposals/ivory-wedding/v1/architecture.png"
                            viewport="desktop"
                            envelopeStyle="luxury"
                            sealStyle="wax"
                        />
                    </div>
                </div>
            </div>
        </section>
    );
}
