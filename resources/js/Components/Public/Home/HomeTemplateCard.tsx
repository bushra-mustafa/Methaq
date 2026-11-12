import { Link } from "@inertiajs/react";
import { ArrowLeft, Eye } from "lucide-react";
import type { LibraryTemplate } from "../../../Types/Library";

interface HomeTemplateCardProps {
    template: LibraryTemplate;
    isAuthenticated: boolean;
}

const categories: Record<
    LibraryTemplate["category"],
    { label: string; description: string }
> = {
    wedding: {
        label: "زفاف",
        description: "بطاقة عرس بظرفها وختمها، تغيّرين فيها النصوص والألوان كما تحبين.",
    },
    henna: {
        label: "حنّة",
        description: "ليلة الحنّة بألوانها وموسيقاها، وكل نص فيها قابل للتعديل.",
    },
    marriage_contract: {
        label: "عقد قران",
        description: "دعوة هادئة لعقد القران، بخطوط عربية وظرف يليق باللحظة.",
    },
    graduation: {
        label: "تخرج",
        description: "احتفلي بتخرجك بدعوة أنيقة تشاركينها مع أهلك وأصدقائك.",
    },
};

/** A featured template card, following the Stitch «قوالب مختارة» card. */
export default function HomeTemplateCard({
    template,
    isAuthenticated,
}: HomeTemplateCardProps) {
    const category = categories[template.category];
    const colors = [...new Set(Object.values(template.palette))].slice(0, 5);
    const useHref = isAuthenticated
        ? `/app/events/create?template=${encodeURIComponent(template.slug)}`
        : "/register";

    return (
        <article className="group flex h-full flex-col overflow-hidden rounded-methaq-card border border-methaq-border bg-methaq-ivory shadow-methaq-card transition-[translate,box-shadow] duration-400 ease-[cubic-bezier(0.16,1,0.3,1)] hover:-translate-y-1.5 hover:shadow-[0_18px_30px_-10px_rgba(139,106,44,0.18),0_6px_14px_-4px_rgba(7,75,54,0.08)] motion-reduce:transition-none motion-reduce:hover:translate-y-0">
            <div className="relative aspect-[4/5] overflow-hidden bg-methaq-sage">
                <img
                    src={template.thumbnailUrl}
                    alt={`معاينة قالب ${template.name}`}
                    width={540}
                    height={960}
                    loading="lazy"
                    className="absolute inset-0 size-full object-cover object-top transition-transform duration-700 group-hover:scale-105 motion-reduce:transition-none motion-reduce:group-hover:scale-100"
                />
                <span className="absolute end-3 top-3 rounded-full bg-methaq-surface/90 px-3 py-1 text-[12px] font-bold text-methaq-gold-dark shadow-sm backdrop-blur-sm">
                    {category.label}
                </span>
            </div>
            <div className="flex flex-1 flex-col justify-between gap-3 p-6">
                <div>
                    <h3 className="m-0 font-methaq-display text-[22px] font-bold text-methaq-primary">
                        {template.name}
                    </h3>
                    <p className="m-0 mt-1.5 text-[14px] leading-relaxed text-methaq-muted">
                        {category.description}
                    </p>
                    {colors.length > 0 && (
                        <div className="mt-3 flex gap-1.5" aria-label="ألوان القالب">
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
                </div>
                <div className="flex items-center justify-between border-t border-methaq-border/60 pt-4">
                    <Link
                        href="/templates"
                        className="inline-flex items-center gap-1 text-[14px] font-semibold text-methaq-gold-dark transition-colors hover:text-methaq-primary"
                    >
                        <Eye size={16} aria-hidden="true" />
                        كل القوالب
                    </Link>
                    <Link
                        href={useHref}
                        className="inline-flex min-h-9 items-center gap-1.5 rounded-md bg-methaq-sage px-4 text-[13px] font-medium text-methaq-primary transition-colors hover:bg-methaq-primary hover:text-white"
                    >
                        {isAuthenticated ? "تخصيص القالب" : "أنشئي حساباً"}
                        <ArrowLeft size={14} aria-hidden="true" />
                    </Link>
                </div>
            </div>
        </article>
    );
}
