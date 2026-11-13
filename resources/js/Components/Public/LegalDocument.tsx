import { Link } from "@inertiajs/react";
import PageIntro from "./PageIntro";
import Reveal from "./Reveal";

export interface LegalSection {
    title: string;
    paragraphs: string[];
}

interface LegalDocumentProps {
    title: string;
    lead: string;
    updated: string;
    sections: LegalSection[];
    /** The sibling document offered at the end, for example terms from the privacy page. */
    related: { href: string; label: string };
}

/** Privacy policy and terms share one reading layout: a page intro, numbered sections, a way to ask. */
export default function LegalDocument({ title, lead, updated, sections, related }: LegalDocumentProps) {
    return (
        <>
            <PageIntro title={title} lead={`${lead} آخر تحديث ${updated}.`} />
            <article className="mx-auto flex max-w-7xl flex-col gap-12 px-6 pb-methaq-section lg:px-12">
                {sections.map((section, index) => (
                    <Reveal
                        as="section"
                        key={section.title}
                        delay={index * 40}
                        className="flex max-w-3xl flex-col gap-4 border-t border-methaq-gold/30 pt-8"
                    >
                        <h2 className="m-0 font-methaq-display text-[26px] font-bold text-methaq-primary">{section.title}</h2>
                        <ul className="m-0 flex list-disc flex-col gap-2 ps-5 text-[16px] leading-[1.9] text-methaq-ink marker:text-methaq-gold">
                            {section.paragraphs.map((paragraph) => (
                                <li key={paragraph}>{paragraph}</li>
                            ))}
                        </ul>
                    </Reveal>
                ))}

                <Reveal as="p" className="m-0 max-w-3xl text-[16px] leading-[1.9] text-methaq-muted">
                    لأي سؤال،{" "}
                    <Link href="/contact" className="text-methaq-primary underline decoration-methaq-gold/60 underline-offset-4">
                        تواصلي معنا
                    </Link>
                    . وراجعي أيضاً{" "}
                    <Link href={related.href} className="text-methaq-primary underline decoration-methaq-gold/60 underline-offset-4">
                        {related.label}
                    </Link>
                    .
                </Reveal>
            </article>
        </>
    );
}
