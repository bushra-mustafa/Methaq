import { Link } from "@inertiajs/react";
import type { LibraryTemplate } from "../../../Types/Library";

interface HomeTemplateCardProps {
    template: LibraryTemplate;
    isAuthenticated: boolean;
}

const categoryLabels: Record<LibraryTemplate["category"], string> = {
    wedding: "زفاف",
    henna: "حنّة",
    marriage_contract: "عقد قران",
    graduation: "تخرج",
};

export default function HomeTemplateCard({
    template,
    isAuthenticated,
}: HomeTemplateCardProps) {
    const colors = [...new Set(Object.values(template.palette))].slice(0, 5);
    const href = isAuthenticated
        ? `/app/events/create?template=${encodeURIComponent(template.slug)}`
        : "/register";

    return (
        <article className="flex flex-col overflow-hidden rounded-methaq-card border border-methaq-gold/20 bg-methaq-surface shadow-methaq-card">
            <div className="relative bg-methaq-paper px-10 pt-8">
                <img
                    src={template.thumbnailUrl}
                    alt={`معاينة قالب ${template.name}`}
                    width={540}
                    height={960}
                    loading="lazy"
                    className="mx-auto block aspect-[9/16] h-auto w-full max-w-[220px] rounded-t-[4px] object-cover shadow-[0_-4px_18px_rgba(4,48,31,0.12)]"
                />
                <span className="absolute end-3 top-3 rounded-full bg-methaq-surface/90 px-3 py-1 text-[12px] text-methaq-gold-dark">
                    {categoryLabels[template.category]}
                </span>
            </div>
            <div className="flex flex-1 flex-col gap-3 border-t border-methaq-gold/15 p-5">
                <h3 className="m-0 text-[20px] font-normal text-methaq-primary">
                    {template.name}
                </h3>
                {colors.length > 0 && (
                    <div className="flex gap-1.5" aria-label="ألوان القالب">
                        {colors.map((color) => (
                            <span
                                key={color}
                                title={color}
                                className="size-4 rounded-full border border-black/10"
                                style={{ backgroundColor: color }}
                            />
                        ))}
                    </div>
                )}
                <Link
                    href={href}
                    className="mt-auto inline-flex min-h-10 touch-manipulation items-center justify-center rounded-methaq-button border border-methaq-primary/20 text-[14px] font-medium text-methaq-primary transition-colors hover:bg-methaq-primary hover:text-white"
                >
                    {isAuthenticated
                        ? "استخدمي هذا القالب"
                        : "أنشئي حساباً للتصميم"}
                </Link>
            </div>
        </article>
    );
}
