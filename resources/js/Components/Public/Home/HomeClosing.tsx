import { Link } from "@inertiajs/react";
import { ArrowLeft, PenLine, Sparkles } from "lucide-react";
import Reveal from "../Reveal";

interface HomeClosingProps {
    isAuthenticated: boolean;
}

/** Final call to action, following the Stitch closing section. */
export default function HomeClosing({ isAuthenticated }: HomeClosingProps) {
    return (
        <section
            aria-labelledby="home-closing-title"
            className="relative overflow-hidden border-t border-methaq-gold-light/20 bg-methaq-primary px-6 py-20 text-center [--methaq-focus-ring:var(--color-methaq-cream)] lg:px-12"
        >
            <div
                aria-hidden="true"
                className="pointer-events-none absolute -top-24 -right-24 size-80 rounded-full bg-methaq-gold-soft/10 blur-3xl"
            />
            <div
                aria-hidden="true"
                className="pointer-events-none absolute -bottom-24 -left-24 size-80 rounded-full bg-methaq-mist/10 blur-3xl"
            />
            <Reveal className="relative mx-auto flex max-w-4xl flex-col items-center gap-7">
                <span className="grid size-14 place-items-center rounded-full border border-methaq-gold-light/40 bg-methaq-ivory/10 text-methaq-gold-light shadow-inner">
                    <Sparkles size={28} aria-hidden="true" />
                </span>
                <div className="flex flex-col gap-3">
                    <h2
                        id="home-closing-title"
                        className="m-0 font-methaq-display text-[36px] leading-tight font-bold text-methaq-cream md:text-[46px]"
                    >
                        حكايتك تستحق دعوة مميزة
                    </h2>
                    <p className="mx-auto m-0 max-w-xl text-[16px] leading-relaxed text-methaq-mist md:text-[18px]">
                        سجّلي حساباً، واختاري قالباً، وشاهدي دعوتك كما سيراها
                        ضيوفك.
                    </p>
                </div>
                <div className="flex flex-col items-center gap-4 pt-2 sm:flex-row">
                    <Link
                        href={isAuthenticated ? "/app" : "/register"}
                        className="inline-flex min-h-12 touch-manipulation items-center gap-3 rounded-methaq-button border border-methaq-gold-light/50 bg-methaq-gold px-8 text-[15px] font-semibold text-white shadow-lg transition-[background-color,translate] duration-300 hover:-translate-y-0.5 hover:bg-methaq-gold-hover motion-reduce:hover:translate-y-0"
                    >
                        <PenLine size={20} aria-hidden="true" />
                        {isAuthenticated ? "افتحي مناسباتك" : "ابدئي تصميم دعوتك"}
                    </Link>
                    <a
                        href="#home-envelope"
                        className="inline-flex min-h-12 items-center gap-2 rounded-methaq-button bg-methaq-ivory/10 px-6 text-[15px] font-medium text-methaq-cream transition-colors hover:bg-methaq-ivory/20"
                    >
                        جرّبي فتح الظرف
                        <ArrowLeft size={18} aria-hidden="true" />
                    </a>
                </div>
            </Reveal>
        </section>
    );
}
