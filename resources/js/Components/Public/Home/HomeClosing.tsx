import { Link } from "@inertiajs/react";
import Reveal from "../Reveal";
import { CornerFlourish } from "./Ornament";

interface HomeClosingProps {
    isAuthenticated: boolean;
}

const corners = [
    "top-4 start-4",
    "top-4 end-4 -scale-x-100",
    "bottom-4 start-4 -scale-y-100",
    "bottom-4 end-4 -scale-100",
];

/** Final call to action: one dark panel inside a gilded double frame. */
export default function HomeClosing({ isAuthenticated }: HomeClosingProps) {
    return (
        <section
            aria-labelledby="home-closing-title"
            className="px-6 py-methaq-section lg:px-12"
        >
            <Reveal className="relative mx-auto max-w-6xl overflow-hidden rounded-[1.25rem] bg-methaq-primary px-8 py-20 text-center text-methaq-cream [--methaq-focus-ring:var(--color-methaq-cream)] md:py-28">
                {corners.map((position) => (
                    <CornerFlourish
                        key={position}
                        className={`pointer-events-none absolute size-12 text-methaq-gold-light/60 md:size-16 ${position}`}
                    />
                ))}
                <div className="relative mx-auto flex max-w-3xl flex-col items-center gap-8">
                    <h2
                        id="home-closing-title"
                        className="m-0 font-methaq-display text-methaq-display font-bold tracking-[-0.01em] text-balance text-methaq-cream"
                    >
                        حكايتك تستحق دعوة مميزة
                    </h2>
                    <p className="m-0 max-w-[40ch] text-methaq-lead text-pretty text-methaq-mist">
                        سجّلي حساباً، واختاري قالباً، وشاهدي دعوتك كما سيراها
                        ضيوفك.
                    </p>
                    <div className="flex flex-col items-center gap-x-8 gap-y-4 sm:flex-row">
                        <Link
                            href={isAuthenticated ? "/app" : "/register"}
                            className="inline-flex min-h-13 touch-manipulation items-center justify-center rounded-methaq-button bg-methaq-cream px-9 text-[16px] font-semibold text-methaq-primary transition-[background-color,scale] duration-200 hover:bg-methaq-gold-soft active:scale-[0.96] motion-reduce:active:scale-100"
                        >
                            {isAuthenticated ? "افتحي مناسباتك" : "ابدئي تصميم دعوتك"}
                        </Link>
                        <a
                            href="#home-envelope-title"
                            className="inline-flex min-h-11 items-center text-[16px] font-medium text-methaq-cream underline decoration-methaq-gold-light/70 decoration-1 underline-offset-8 transition-[text-decoration-color] hover:decoration-methaq-gold-light"
                        >
                            جرّبي فتح الظرف
                        </a>
                    </div>
                </div>
            </Reveal>
        </section>
    );
}
