import { Link } from "@inertiajs/react";
import HomeEnvelopeShowcase from "./HomeEnvelopeShowcase";

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
            className="px-6 py-14 lg:px-12 lg:py-20"
        >
            <div className="mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-2 lg:gap-10">
                <div className="flex min-w-0 flex-col items-start gap-6 text-right">
                    <h1
                        id="home-hero-title"
                        className="m-0 max-w-[14ch] font-methaq-display text-[clamp(36px,5vw,56px)] font-normal text-balance text-methaq-primary"
                    >
                        لكل حكاية جميلة… دعوةٌ تليق بها
                    </h1>

                    <p className="m-0 max-w-[44ch] text-[17px] leading-[1.9] text-pretty text-methaq-muted">
                        صمّمي دعوتك، وشاركيها برابط يفتحه ضيوفك كظرف حقيقي،
                        ويؤكدون حضورهم من الصفحة نفسها.
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
