import type { PropsWithChildren } from "react";
import { EightPointStar } from "./Home/Ornament";

interface PageIntroProps {
    title: string;
    /** The one sentence that says what this page is for. */
    lead: string;
    id?: string;
}

/** The opening of every inner public page: a large Amiri title, one lead sentence, a faint rosette. */
export default function PageIntro({ title, lead, id = "page-title", children }: PropsWithChildren<PageIntroProps>) {
    return (
        <header className="relative isolate overflow-hidden px-6 pt-methaq-section pb-14 lg:px-12">
            <EightPointStar
                className="pointer-events-none absolute -z-10 hidden size-[30rem] max-w-none text-methaq-gold/25 lg:block"
                style={{ insetInlineEnd: "-4rem", top: "-6rem" }}
            />
            <div className="mx-auto flex max-w-7xl flex-col gap-6">
                <h1
                    id={id}
                    className="m-0 max-w-4xl font-methaq-display text-methaq-display font-bold tracking-[-0.01em] text-balance text-methaq-primary motion-safe:animate-methaq-hero-in"
                >
                    {title}
                </h1>
                <p
                    className="m-0 max-w-[48ch] text-methaq-lead text-pretty text-methaq-muted motion-safe:animate-methaq-hero-in"
                    style={{ animationDelay: "140ms" }}
                >
                    {lead}
                </p>
                {children}
            </div>
        </header>
    );
}
