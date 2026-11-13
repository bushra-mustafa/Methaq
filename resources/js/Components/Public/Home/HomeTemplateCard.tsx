import { Link } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";
import { trackEvent } from "../../../Domains/Privacy/analytics";
import Thumbnail from "../../Thumbnail";
import { categoryLabels } from "../../../Types/Library";
import type { LibraryTemplate } from "../../../Types/Library";

interface HomeTemplateCardProps {
    template: LibraryTemplate;
    isAuthenticated: boolean;
    /** Alternates the tilt and the vertical offset so the row reads as laid by hand. */
    index: number;
}

/** One featured template: the whole invitation, as a sheet of paper resting on the marble. */
export default function HomeTemplateCard({
    template,
    isAuthenticated,
    index,
}: HomeTemplateCardProps) {
    const colors = [...new Set(Object.values(template.palette))].slice(0, 5);
    const useHref = `/start/${encodeURIComponent(template.slug)}`;
    const tilt = ["-rotate-[1.5deg]", "rotate-1", "-rotate-[0.75deg]", "rotate-[1.25deg]"][index % 4];
    const drop = index % 2 === 1 ? "lg:translate-y-10" : "";

    return (
        <figure className={`group m-0 flex flex-col gap-5 ${drop}`}>
            <div
                className={`${tilt} bg-white p-2 shadow-[0_1px_2px_rgba(32,51,34,0.14),0_14px_28px_-10px_rgba(32,51,34,0.32),0_30px_44px_-18px_rgba(122,97,59,0.28)] outline-1 -outline-offset-1 outline-black/10 transition-[rotate] duration-500 ease-[cubic-bezier(0.2,0,0,1)] hover:rotate-0 motion-reduce:transition-none`}
            >
                <Thumbnail
                    src={template.thumbnailUrl}
                    avif={template.thumbnailAvifUrl}
                    webp={template.thumbnailWebpUrl}
                    alt={`معاينة قالب ${template.name}`}
                    width={540}
                    height={960}
                    className="block aspect-[9/16] w-full bg-methaq-sage object-cover"
                />
            </div>
            <figcaption className="flex flex-col gap-3 px-1">
                <div className="flex items-baseline justify-between gap-3">
                    <h3 className="m-0 font-methaq-display text-[24px] leading-[1.35] font-bold text-methaq-primary">
                        {template.name}
                    </h3>
                    <span className="shrink-0 text-[14px] text-methaq-muted">
                        {categoryLabels[template.category]}
                    </span>
                </div>
                {colors.length > 0 && (
                    <div className="flex gap-1.5" role="img" aria-label="ألوان القالب">
                        {colors.map((color) => (
                            <span
                                key={color}
                                className="size-4 rounded-full outline-1 -outline-offset-1 outline-black/10"
                                style={{ backgroundColor: color }}
                            />
                        ))}
                    </div>
                )}
                <Link
                    href={useHref}
                    onClick={() => trackEvent("template_start")}
                    className="inline-flex min-h-11 items-center gap-2 self-start text-[15px] font-semibold text-methaq-primary underline decoration-methaq-gold/60 decoration-1 underline-offset-8 transition-[text-decoration-color] hover:decoration-methaq-gold"
                >
                    {isAuthenticated ? "استخدمي هذا القالب" : "أنشئي حساباً لاستخدامه"}
                    <ArrowLeft size={16} aria-hidden="true" />
                </Link>
            </figcaption>
        </figure>
    );
}
