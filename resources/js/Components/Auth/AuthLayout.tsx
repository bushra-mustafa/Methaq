import { Link } from "@inertiajs/react";
import type { PropsWithChildren, ReactNode } from "react";

interface AuthLayoutProps {
    title: string;
    description: string;
    footer?: ReactNode;
}

/** The path from sign-up to a published invitation; the reason to try Methaq. */
const steps = [
    { title: "صمّمي", text: "اختاري قالباً وعدّلي نصوصه وألوانه." },
    { title: "شاهدي", text: "جرّبي فتح الظرف قبل أن تنشري." },
    { title: "انشري", text: "ادفعي بالتحويل البنكي عند النشر فقط." },
];

const arabicDigits = ["١", "٢", "٣"];

/**
 * Every account page is a letter on the ink-green desk: a sheet of paper to write on, a wax
 * seal to press, and below it the three steps that make Methaq worth trying.
 */
export default function AuthLayout({
    title,
    description,
    footer,
    children,
}: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div
            className="methaq-public relative isolate flex min-h-dvh flex-col items-center gap-12 overflow-hidden bg-methaq-primary! bg-[radial-gradient(56rem_28rem_at_50%_-8%,rgba(226,197,157,0.16),transparent)] px-5 py-8 font-methaq-body text-methaq-cream! [--methaq-focus-ring:var(--color-methaq-gold-light)] sm:py-12"
            dir="rtl"
            lang="ar"
        >
            <span
                aria-hidden="true"
                className="pointer-events-none absolute inset-x-0 -bottom-[0.18em] -z-10 select-none text-center font-methaq-display text-[min(34vw,30rem)] leading-none font-bold text-methaq-cream/[0.045]"
            >
                ميثاق
            </span>

            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[60] focus:rounded-methaq-button focus:bg-methaq-cream focus:px-5 focus:py-3 focus:text-[15px] focus:font-semibold focus:text-methaq-primary"
            >
                الانتقال إلى النموذج
            </a>

            <Link href="/" aria-label="ميثاق، العودة إلى الرئيسية" className="rounded-methaq-button">
                <img
                    src="/brand/logos/methaq-horizontal-compact-flat.svg"
                    alt=""
                    width={196}
                    height={43}
                    className="block h-10 w-auto brightness-0 invert"
                />
            </Link>

            <div className="relative w-full max-w-[28rem] motion-safe:animate-methaq-hero-in">
                <span
                    aria-hidden="true"
                    className="absolute inset-0 -z-10 rotate-[2.2deg] bg-[#e9e2cf] shadow-[0_18px_30px_-12px_rgba(0,0,0,0.5)]"
                />
                <span
                    aria-hidden="true"
                    className="absolute inset-0 -z-10 -rotate-[1.4deg] bg-[#f1ead8] shadow-[0_18px_30px_-12px_rgba(0,0,0,0.5)]"
                />
                <main
                    id="main-content"
                    tabIndex={-1}
                    className="bg-methaq-cream px-6 pt-10 pb-8 text-methaq-ink shadow-[0_24px_46px_-16px_rgba(0,0,0,0.55)] outline-none [--methaq-focus-ring:var(--color-methaq-primary)] sm:px-10"
                >
                    <header className="flex flex-col gap-3 pb-8">
                        <h1 className="m-0 font-methaq-display text-[clamp(2.25rem,1.8rem+1.6vw,3rem)] leading-[1.25] font-bold tracking-[-0.01em] text-balance text-methaq-primary">
                            {title}
                        </h1>
                        <p className="m-0 max-w-[36ch] text-[16px] leading-[1.8] text-pretty text-methaq-muted">
                            {description}
                        </p>
                    </header>

                    {children}

                    {footer && (
                        <footer className="mt-7 border-t border-dashed border-methaq-gold/40 pt-5 text-center text-[15px] text-methaq-muted [&_a]:font-semibold [&_a]:text-methaq-primary [&_a]:underline [&_a]:decoration-methaq-gold/60 [&_a]:underline-offset-8 [&_a]:transition-[text-decoration-color] [&_a:hover]:decoration-methaq-gold [&_button]:font-semibold [&_button]:text-methaq-primary [&_button]:underline [&_button]:decoration-methaq-gold/60 [&_button]:underline-offset-8">
                            {footer}
                        </footer>
                    )}
                </main>
            </div>

            <ol className="m-0 grid w-full max-w-3xl list-none gap-7 p-0 sm:grid-cols-3">
                {steps.map((step, index) => (
                    <li key={step.title} className="flex items-start gap-4">
                        <span
                            aria-hidden="true"
                            className="font-methaq-display text-[2.5rem] leading-none text-methaq-gold-light"
                        >
                            {arabicDigits[index]}
                        </span>
                        <span className="flex flex-col gap-1 pt-1">
                            <strong className="text-[17px] font-bold text-methaq-cream">{step.title}</strong>
                            <span className="text-[15px] leading-[1.8] text-methaq-mist">{step.text}</span>
                        </span>
                    </li>
                ))}
            </ol>

            <Link
                href="/templates"
                className="mt-auto inline-flex min-h-11 items-center text-[15px] font-medium text-methaq-gold-light underline decoration-methaq-gold-light/50 underline-offset-8 transition-[text-decoration-color] hover:decoration-methaq-gold-light"
            >
                تصفّحي القوالب بدون حساب
            </Link>
        </div>
    );
}
