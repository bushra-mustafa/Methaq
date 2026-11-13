import { Link } from "@inertiajs/react";
import { Eye, PenLine, Send } from "lucide-react";
import type { PropsWithChildren, ReactNode } from "react";

interface AuthLayoutProps {
    title: string;
    description: string;
    footer?: ReactNode;
}

/** Why a visitor who only came to sign in might want to try Methaq. */
const steps = [
    { icon: PenLine, title: "صمّمي", text: "اختاري قالباً وعدّلي نصوصه وألوانه." },
    { icon: Eye, title: "شاهدي", text: "جرّبي فتح الظرف قبل أن تنشري." },
    { icon: Send, title: "انشري", text: "ادفعي بالتحويل البنكي عند النشر فقط." },
];

/** Every account page: a clean form card, and under it the three steps that make Methaq worth trying. */
export default function AuthLayout({
    title,
    description,
    footer,
    children,
}: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div
            className="methaq-public flex min-h-dvh flex-col items-center gap-10 bg-methaq-ivory px-5 py-8 font-methaq-body text-methaq-ink sm:py-12"
            dir="rtl"
            lang="ar"
        >
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[60] focus:rounded-methaq-button focus:bg-methaq-primary focus:px-5 focus:py-3 focus:text-[15px] focus:font-semibold focus:text-methaq-cream"
            >
                الانتقال إلى النموذج
            </a>

            <Link href="/" aria-label="ميثاق، العودة إلى الرئيسية">
                <img
                    src="/brand/logos/methaq-horizontal-compact-flat.svg"
                    alt=""
                    width={196}
                    height={43}
                    className="block h-10 w-auto"
                />
            </Link>

            <main
                id="main-content"
                tabIndex={-1}
                className="w-full max-w-[28rem] rounded-[1rem] border border-methaq-border bg-white px-6 py-9 shadow-methaq-raised outline-none motion-safe:animate-methaq-hero-in sm:px-10"
            >
                <header className="flex flex-col items-center gap-3 pb-8 text-center">
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
            </main>

            <ul className="m-0 grid w-full max-w-3xl list-none gap-6 p-0 sm:grid-cols-3">
                {steps.map(({ icon: Icon, title: stepTitle, text }) => (
                    <li key={stepTitle} className="flex items-start gap-4">
                        <span className="grid size-11 shrink-0 place-items-center rounded-full bg-methaq-gold-soft/60 text-methaq-gold-dark">
                            <Icon size={20} aria-hidden="true" />
                        </span>
                        <span className="flex flex-col gap-1">
                            <strong className="text-[16px] font-bold text-methaq-primary">
                                {stepTitle}
                            </strong>
                            <span className="text-[15px] leading-[1.8] text-methaq-muted">
                                {text}
                            </span>
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}
