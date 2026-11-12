import { Link } from "@inertiajs/react";
import { Sparkles } from "lucide-react";
import type { LibraryTemplate } from "../../../Types/Library";
import Reveal from "../Reveal";
import HomeTemplateCard from "./HomeTemplateCard";

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
            className="scroll-mt-24 bg-methaq-sage px-6 py-20 lg:px-12"
        >
            <div className="mx-auto flex max-w-7xl flex-col gap-12">
                <Reveal className="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                    <div className="flex flex-col gap-2">
                        <span className="text-[13px] font-semibold tracking-widest text-methaq-gold-dark">
                            اختيارات ميثاق
                        </span>
                        <h2
                            id="home-templates-title"
                            className="m-0 font-methaq-display text-[32px] leading-tight font-bold text-methaq-primary md:text-[38px]"
                        >
                            قوالب مختارة من ميثاق
                        </h2>
                        <p className="m-0 max-w-2xl text-[16px] leading-relaxed text-methaq-muted">
                            نقطة بداية لكل مناسبة؛ غيّري فيها النصوص والألوان
                            والخطوط والظرف كما تحبين.
                        </p>
                    </div>
                    <Link
                        href="/templates"
                        className="inline-flex items-center gap-2 text-[15px] font-medium text-methaq-primary underline decoration-methaq-gold/60 underline-offset-8 transition-[text-decoration-color] hover:decoration-methaq-gold"
                    >
                        <Sparkles
                            size={18}
                            aria-hidden="true"
                            className="text-methaq-gold"
                        />
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
                    <ul className="m-0 grid list-none grid-cols-1 gap-8 p-0 md:grid-cols-2 lg:grid-cols-3">
                        {templates.map((template, index) => (
                            <Reveal
                                as="li"
                                key={template.id}
                                delay={(index % 3) * 120}
                            >
                                <HomeTemplateCard
                                    template={template}
                                    isAuthenticated={isAuthenticated}
                                />
                            </Reveal>
                        ))}
                    </ul>
                )}
            </div>
        </section>
    );
}
