import { Link } from "@inertiajs/react";
import type { LibraryTemplate } from "../../../Types/Library";
import HomeTemplateCard from "./HomeTemplateCard";

interface HomeTemplatesProps {
    templates: LibraryTemplate[];
    isAuthenticated: boolean;
}

export default function HomeTemplates({
    templates,
    isAuthenticated,
}: HomeTemplatesProps) {
    return (
        <section
            aria-labelledby="home-templates-title"
            className="bg-methaq-sage px-6 py-20 lg:px-12 lg:py-24"
        >
            <div className="mx-auto flex max-w-7xl flex-col gap-12">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                    <div className="flex flex-col gap-3">
                        <h2
                            id="home-templates-title"
                            className="m-0 font-methaq-display text-[clamp(28px,3.4vw,38px)] font-normal text-methaq-primary"
                        >
                            قوالب مختارة من ميثاق
                        </h2>
                        <p className="m-0 max-w-[52ch] text-[16px] leading-[1.9] text-methaq-muted">
                            نقطة بداية لكل مناسبة؛ غيّري فيها النصوص والألوان
                            والخطوط كما تحبين.
                        </p>
                    </div>
                    <Link
                        href="/templates"
                        className="text-[15px] font-medium text-methaq-primary underline decoration-methaq-gold/60 underline-offset-8 transition-[text-decoration-color] hover:decoration-methaq-gold"
                    >
                        عرض كل القوالب
                    </Link>
                </div>

                {templates.length === 0 ? (
                    <p className="m-0 text-[16px] text-methaq-muted">
                        القوالب قيد التجهيز. تصفحي{" "}
                        <Link
                            href="/templates"
                            className="text-methaq-primary underline underline-offset-4"
                        >
                            مكتبة القوالب
                        </Link>{" "}
                        لاحقاً.
                    </p>
                ) : (
                    <div
                        className={`grid gap-8 sm:grid-cols-2 ${templates.length % 4 === 0 ? "lg:grid-cols-4" : "lg:grid-cols-3"}`}
                    >
                        {templates.map((template) => (
                            <HomeTemplateCard
                                key={template.id}
                                template={template}
                                isAuthenticated={isAuthenticated}
                            />
                        ))}
                    </div>
                )}
            </div>
        </section>
    );
}
