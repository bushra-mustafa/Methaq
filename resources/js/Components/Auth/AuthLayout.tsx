import { Link } from "@inertiajs/react";
import type { PropsWithChildren, ReactNode } from "react";
import AtelierScene from "./AtelierScene";
import SilkBackdrop from "./SilkBackdrop";

interface AuthLayoutProps {
    title: string;
    description: string;
    footer?: ReactNode;
}

const Logo = ({ className = "" }: { className?: string }) => (
    <Link href="/" aria-label="ميثاق، العودة إلى الرئيسية" className={`inline-block rounded-methaq-button ${className}`}>
        <img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="" width={196} height={43} className="block h-10 w-auto" />
    </Link>
);

/**
 * Every account page, in two parts: the form (45%, on the reading side) as a plain modern
 * interface, and the atelier (55%): silk, two invitations, a sealed envelope, one sentence.
 * Phones get a compact header with a small preview instead of the atelier.
 */
export default function AuthLayout({
    title,
    description,
    footer,
    children,
}: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div
            className="methaq-public min-h-dvh bg-methaq-ivory font-methaq-body text-methaq-ink lg:grid lg:grid-cols-[45fr_55fr]"
            dir="rtl"
            lang="ar"
        >
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[60] focus:rounded-methaq-button focus:bg-methaq-primary focus:px-5 focus:py-3 focus:text-[15px] focus:font-semibold focus:text-methaq-cream"
            >
                الانتقال إلى النموذج
            </a>

            <div className="flex min-h-dvh flex-col px-5 py-6 sm:px-10 lg:px-14 lg:py-10 xl:px-20">
                <Logo className="self-start" />

                <main
                    id="main-content"
                    tabIndex={-1}
                    className="mx-auto flex w-full max-w-[26rem] flex-1 flex-col justify-center gap-7 py-8 outline-none lg:py-10"
                >
                    <div className="relative isolate -mx-5 overflow-hidden rounded-[1rem] px-5 pt-5 pb-1 sm:mx-0 lg:hidden">
                        <SilkBackdrop className="rounded-[1rem]" />
                        <AtelierScene className="mx-auto max-w-[17rem]" />
                    </div>

                    <header className="flex flex-col gap-3 motion-safe:animate-methaq-hero-in">
                        <h1 className="m-0 font-methaq-display text-[clamp(2.25rem,1.8rem+1.6vw,3rem)] leading-[1.25] font-bold tracking-[-0.01em] text-balance text-methaq-primary">
                            {title}
                        </h1>
                        <p className="m-0 max-w-[36ch] text-[16px] leading-[1.8] text-pretty text-methaq-muted">
                            {description}
                        </p>
                    </header>

                    <div className="motion-safe:animate-methaq-hero-in" style={{ animationDelay: "120ms" }}>
                        {children}
                    </div>

                    {footer && (
                        <footer className="border-t border-methaq-border pt-6 text-[15px] text-methaq-muted [&_a]:font-semibold [&_a]:text-methaq-primary [&_a]:underline [&_a]:decoration-methaq-gold/60 [&_a]:underline-offset-8 [&_a]:transition-[text-decoration-color] [&_a:hover]:decoration-methaq-gold [&_button]:font-semibold [&_button]:text-methaq-primary [&_button]:underline [&_button]:decoration-methaq-gold/60 [&_button]:underline-offset-8">
                            {footer}
                        </footer>
                    )}
                </main>
            </div>

            <aside
                aria-label="ميثاق"
                className="relative isolate hidden min-h-dvh flex-col items-center justify-center gap-12 overflow-hidden px-12 py-14 text-methaq-cream lg:flex xl:px-20"
            >
                <SilkBackdrop />
                <AtelierScene className="max-w-[34rem]" />
                <div className="flex w-full max-w-[34rem] flex-col gap-4">
                    <span aria-hidden="true" className="h-px w-14 bg-methaq-gold-light" />
                    <p className="m-0 font-methaq-display text-[clamp(1.9rem,1.4rem+1.2vw,2.6rem)] leading-[1.35] font-bold text-balance text-methaq-cream">
                        كل حكاية جميلة تبدأ بدعوة
                    </p>
                    <p className="m-0 max-w-[44ch] text-[17px] leading-[1.9] text-pretty text-methaq-mist">
                        صمّمي بطاقتك، وشاركيها برابط واحد يفتحه ضيوفك كظرف حقيقي.
                    </p>
                </div>
            </aside>
        </div>
    );
}
