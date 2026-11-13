import { Head, Link, usePage } from "@inertiajs/react";
import { Check } from "lucide-react";
import { LibraryTemplateCard } from "../../Domains/Editor/Components/LibraryTemplateCard";
import PaperSurface from "../../Components/Public/Home/PaperSurface";
import PageIntro from "../../Components/Public/PageIntro";
import Reveal from "../../Components/Public/Reveal";
import PublicLayout from "../../Layouts/PublicLayout";
import type { LibraryTemplate } from "../../Types/Library";
import type { SharedPageProps } from "../../Types/PageProps";
import "../../../css/library.css";

interface OccasionProps {
    occasion: { slug: string; label: string; headline: string; description: string; points: string[] };
    templates: LibraryTemplate[];
    occasions: { slug: string; label: string }[];
}

/** One landing page per occasion: what Methaq gives it, the priced templates for it, and the other occasions. */
export default function Occasion({ occasion, templates, occasions }: OccasionProps) {
    const { auth } = usePage<SharedPageProps>().props;

    return (
        <PublicLayout>
            <Head title={`دعوات ${occasion.label} إلكترونية`} />
            <PageIntro title={occasion.headline} lead={occasion.description}>
                <div className="flex flex-wrap items-center gap-x-8 gap-y-4">
                    <Link
                        href={auth.user ? "/app/events/create" : "/register"}
                        className="inline-flex min-h-13 items-center justify-center rounded-methaq-button bg-methaq-primary px-8 text-[16px] font-semibold text-white shadow-methaq-soft transition-colors hover:bg-methaq-primary-hover"
                    >
                        {auth.user ? "أنشئي مناسبة" : "صمّمي دعوتك"}
                    </Link>
                    <a href="#occasion-templates" className="inline-flex min-h-11 items-center text-[16px] font-medium text-methaq-primary underline decoration-methaq-gold/60 underline-offset-8 hover:decoration-methaq-gold">
                        شاهدي القوالب
                    </a>
                </div>
            </PageIntro>

            <section aria-label="ما تحصلين عليه" className="px-6 pb-14 lg:px-12">
                <ul className="mx-auto m-0 flex max-w-7xl list-none flex-col gap-4 border-t border-methaq-gold/30 p-0 pt-8 md:flex-row md:gap-12">
                    {occasion.points.map((point) => (
                        <li key={point} className="flex flex-1 items-start gap-3 text-[17px] leading-[1.9] text-methaq-ink">
                            <Check size={20} aria-hidden="true" className="mt-1.5 shrink-0 text-methaq-gold-dark" />
                            <span>{point}</span>
                        </li>
                    ))}
                </ul>
            </section>

            <section className="library-page" aria-labelledby="occasion-templates">
                <div className="library-section">
                    <div className="library-section-heading">
                        <h2 id="occasion-templates">قوالب {occasion.label}</h2>
                        <p>{templates.length > 0 ? `${templates.length} قوالب جاهزة للتعديل` : "قوالب هذه المناسبة قيد التجهيز"}</p>
                    </div>
                    {templates.length === 0 ? (
                        <p className="library-empty">نجهّز قوالب {occasion.label} الآن. تصفحي <Link href="/templates" className="underline underline-offset-4">كل القوالب</Link> في الأثناء.</p>
                    ) : (
                        <PaperSurface>
                            <div className="library-template-grid">
                                {templates.map((template, index) => (
                                    <Reveal key={template.id} delay={(index % 4) * 80}>
                                        <LibraryTemplateCard template={template} />
                                    </Reveal>
                                ))}
                            </div>
                        </PaperSurface>
                    )}
                </div>
            </section>

            <nav aria-label="مناسبات أخرى" className="px-6 pb-methaq-section lg:px-12">
                <ul className="mx-auto m-0 flex max-w-7xl list-none flex-wrap gap-3 p-0">
                    {occasions.filter((other) => other.slug !== occasion.slug).map((other) => (
                        <li key={other.slug}>
                            <Link href={`/occasions/${other.slug}`} className="inline-flex min-h-11 items-center rounded-full border border-methaq-primary/30 px-5 text-[15px] text-methaq-primary transition-colors hover:border-methaq-primary">
                                دعوات {other.label}
                            </Link>
                        </li>
                    ))}
                </ul>
            </nav>
        </PublicLayout>
    );
}
