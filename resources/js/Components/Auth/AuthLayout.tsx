import { Link } from "@inertiajs/react";
import type { PropsWithChildren, ReactNode } from "react";
import { CornerFlourish } from "../Public/Home/Ornament";
import AuthShowcase from "./AuthShowcase";
import type { AuthPromoKey } from "./authPromos";

interface AuthLayoutProps {
    title: string;
    description: string;
    footer?: ReactNode;
    promo?: AuthPromoKey;
}

const corners = [
    "start-3 top-3",
    "end-3 top-3 -scale-x-100",
    "start-3 bottom-3 -scale-y-100",
    "end-3 bottom-3 -scale-100",
];

/**
 * Every account page: the form on a gilded sheet of paper beside a promotion of the
 * product itself, so a visitor who only came to sign in still sees what Methaq makes.
 */
export default function AuthLayout({
    title,
    description,
    footer,
    promo = "account",
    children,
}: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div
            className="methaq-public min-h-dvh bg-methaq-ivory font-methaq-body text-methaq-ink lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]"
            dir="rtl"
            lang="ar"
        >
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[60] focus:rounded-methaq-button focus:bg-methaq-primary focus:px-5 focus:py-3 focus:text-[15px] focus:font-semibold focus:text-methaq-cream"
            >
                الانتقال إلى النموذج
            </a>

            <main
                id="main-content"
                tabIndex={-1}
                className="flex flex-col gap-8 px-5 py-6 outline-none sm:px-10 lg:min-h-dvh lg:px-14"
            >
                <header className="flex items-center justify-between gap-4">
                    <Link href="/" aria-label="ميثاق، العودة إلى الرئيسية">
                        <img
                            src="/brand/logos/methaq-horizontal-compact-flat.svg"
                            alt=""
                            width={196}
                            height={43}
                            className="block h-9 w-auto"
                        />
                    </Link>
                    <Link
                        href="/templates"
                        className="inline-flex min-h-11 items-center text-[15px] font-medium text-methaq-primary underline decoration-methaq-gold/60 decoration-1 underline-offset-8 transition-[text-decoration-color] hover:decoration-methaq-gold"
                    >
                        تصفّحي القوالب
                    </Link>
                </header>

                <div className="flex flex-1 items-start justify-center pb-6 lg:items-center">
                    <div className="relative w-full max-w-[29rem] bg-white px-7 py-10 shadow-[0_1px_2px_rgba(32,51,34,0.12),0_18px_36px_-14px_rgba(32,51,34,0.28),0_34px_50px_-22px_rgba(122,97,59,0.26)] outline-1 -outline-offset-1 outline-black/10 motion-safe:animate-methaq-hero-in sm:px-11">
                        {corners.map((position) => (
                            <CornerFlourish
                                key={position}
                                className={`pointer-events-none absolute size-9 text-methaq-gold/55 ${position}`}
                            />
                        ))}

                        <header className="flex flex-col items-center gap-3 pb-8 pt-2 text-center">
                            <h1 className="m-0 font-methaq-display text-[clamp(2rem,1.6rem+1.4vw,2.6rem)] leading-[1.3] font-bold tracking-[-0.01em] text-balance text-methaq-primary">
                                {title}
                            </h1>
                            <p className="m-0 max-w-[34ch] text-[16px] leading-[1.8] text-pretty text-methaq-muted">
                                {description}
                            </p>
                        </header>

                        {children}

                        {footer && (
                            <footer className="mt-8 border-t border-methaq-border pt-6 text-center text-[15px] text-methaq-muted [&_a]:font-semibold [&_a]:text-methaq-primary [&_a]:underline [&_a]:decoration-methaq-gold/60 [&_a]:underline-offset-8 [&_a]:transition-[text-decoration-color] [&_a:hover]:decoration-methaq-gold [&_button]:font-semibold [&_button]:text-methaq-primary [&_button]:underline [&_button]:decoration-methaq-gold/60 [&_button]:underline-offset-8">
                                {footer}
                            </footer>
                        )}
                    </div>
                </div>
            </main>

            <AuthShowcase promo={promo} />
        </div>
    );
}
