import { Link } from "@inertiajs/react";
import { trackEvent } from "../../../Domains/Privacy/analytics";
import type { LibraryTemplate } from "../../../Types/Library";
import Reveal from "../Reveal";
import HomeTemplateCard from "./HomeTemplateCard";
import PaperSurface from "./PaperSurface";

interface HomeTemplatesProps {
    templates: LibraryTemplate[];
    isAuthenticated: boolean;
}

/** Templates the admin marked «عرض في الرئيسية» in the library. */
export default function HomeTemplates({
    templates,
    isAuthenticated,
}: HomeTemplatesProps) {
    return (
        <section
            id="home-templates"
            aria-labelledby="home-templates-title"
            className="scroll-mt-24 px-6 pb-methaq-section lg:px-12"
        >
            <div className="mx-auto flex max-w-7xl flex-col gap-12">
                <Reveal className="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                    <div className="flex max-w-2xl flex-col gap-4">
                        <h2
                            id="home-templates-title"
                            className="m-0 font-methaq-display text-methaq-h2 font-bold tracking-[-0.01em] text-balance text-methaq-primary"
                        >
                            قوالب مختارة من ميثاق
                        </h2>
                        <p className="m-0 text-methaq-lead text-pretty text-methaq-muted">
                            نقطة بداية لكل مناسبة؛ غيّري فيها النصوص والألوان
                            والخطوط والظرف كما تحبين.
                        </p>
                    </div>
                    <Link
                        href="/templates"
                        onClick={() => trackEvent("browse_templates")}
                        className="inline-flex min-h-11 items-center text-[16px] font-medium text-methaq-primary underline decoration-methaq-gold/60 decoration-1 underline-offset-8 transition-[text-decoration-color] hover:decoration-methaq-gold"
                    >
                        عرض كل القوالب
                    </Link>
                </Reveal>

                {templates.length === 0 ? (
                    <p className="m-0 text-[16px] text-methaq-muted">
                        نجهّز اختياراتنا لكِ. تصفحي{" "}
                        <Link
                            href="/templates"
                            className="text-methaq-primary underline underline-offset-4"
                        >
                            مكتبة القوالب
                        </Link>{" "}
                        الآن.
                    </p>
                ) : (
                    <PaperSurface>
                        <div
                            role="region"
                            aria-label="قوالب مختارة، مرّري للمزيد"
                            tabIndex={0}
                            className="snap-x snap-mandatory overflow-x-auto overscroll-x-contain p-5 pt-8 pb-16 [mask-image:linear-gradient(to_right,transparent,black_4%,black_96%,transparent)] [scrollbar-width:thin] focus-visible:outline-2 focus-visible:-outline-offset-4 focus-visible:outline-methaq-primary sm:p-8 sm:pt-10 sm:pb-20 lg:p-12 lg:pb-24"
                        >
                            <ul className="m-0 flex w-max list-none gap-6 p-0 sm:gap-8 lg:w-full lg:justify-center lg:gap-6">
                                {templates.map((template, index) => (
                                    <li
                                        key={template.id}
                                        className="w-[min(62vw,17rem)] shrink-0 snap-start scroll-ms-5 sm:scroll-ms-8 lg:w-auto lg:min-w-0 lg:flex-[0_1_15rem]"
                                    >
                                        <HomeTemplateCard
                                            template={template}
                                            isAuthenticated={isAuthenticated}
                                            index={index}
                                        />
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </PaperSurface>
                )}
            </div>
        </section>
    );
}
