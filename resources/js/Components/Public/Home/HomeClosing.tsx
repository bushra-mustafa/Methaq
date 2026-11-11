import { Link } from "@inertiajs/react";

interface HomeClosingProps {
    isAuthenticated: boolean;
}

export default function HomeClosing({ isAuthenticated }: HomeClosingProps) {
    return (
        <section
            aria-labelledby="home-closing-title"
            className="bg-methaq-primary px-6 py-20 text-center [--methaq-focus-ring:var(--color-methaq-cream)]"
        >
            <div className="mx-auto flex max-w-2xl flex-col items-center gap-5">
                <h2
                    id="home-closing-title"
                    className="m-0 font-methaq-display text-[clamp(28px,4vw,40px)] font-normal text-methaq-cream"
                >
                    ابدئي دعوتك اليوم
                </h2>
                <p className="m-0 max-w-[46ch] text-[16px] leading-[1.9] text-methaq-cream/80">
                    سجّلي حساباً، واختاري قالباً، وشاهدي دعوتك كما سيراها ضيوفك.
                </p>
                <Link
                    href={isAuthenticated ? "/app" : "/register"}
                    className="mt-2 inline-flex min-h-12 touch-manipulation items-center justify-center rounded-methaq-button bg-methaq-cream px-7 text-[15px] font-semibold text-methaq-primary transition-colors hover:bg-white"
                >
                    {isAuthenticated ? "افتحي مناسباتك" : "صمّمي دعوتك"}
                </Link>
            </div>
        </section>
    );
}
