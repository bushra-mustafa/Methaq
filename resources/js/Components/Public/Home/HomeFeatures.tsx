import {
    LayoutTemplate,
    Link2,
    ListChecks,
    Mail,
    Music,
    PenLine,
} from "lucide-react";
import type { LucideIcon } from "lucide-react";
import Reveal from "../Reveal";
import { EightPointStar } from "./Ornament";

interface Feature {
    icon: LucideIcon;
    title: string;
    description: string;
    comingSoon?: boolean;
    /** The lead feature gets two columns and a larger title. */
    lead?: boolean;
}

const features: Feature[] = [
    {
        icon: PenLine,
        title: "محرر بطاقة مرئي",
        description:
            "غيّري النصوص والألوان والخطوط، ورتّبي عناصر البطاقة كما تحبين، وشاهدي النتيجة فور تعديلها.",
        lead: true,
    },
    {
        icon: Mail,
        title: "ظرف وختم وخامة",
        description: "اختاري شكل الظرف ونوع الختم وخامة الورق لتناسب مناسبتك.",
    },
    {
        icon: Music,
        title: "موسيقى ترحيبية",
        description:
            "مقاطع من مكتبة ميثاق، مع تشغيل وكتم والتحكم بمستوى الصوت.",
    },
    {
        icon: Link2,
        title: "رابط خاص لمناسبتك",
        description: "لكل مناسبة منشورة عنوانها الخاص، تشاركينه مع ضيوفك.",
    },
    {
        icon: LayoutTemplate,
        title: "قوالب لكل مناسبة",
        description: "زفاف وحنّة وعقد قران وتخرج، ولكل قالب ألوانه وزخارفه.",
    },
    {
        icon: ListChecks,
        title: "تأكيد الحضور",
        description:
            "يرد ضيوفك بالحضور أو الاعتذار وعدد المرافقين دون إنشاء حساب.",
        comingSoon: true,
    },
];

/**
 * What the platform gives you. A heading on one side and a ruled list on the other:
 * no boxes, so the page does not repeat the card pattern of the template section.
 */
export default function HomeFeatures() {
    return (
        <section
            id="home-features"
            aria-labelledby="home-features-title"
            className="scroll-mt-24 px-6 pt-methaq-section lg:px-12"
        >
            <div className="mx-auto grid max-w-7xl gap-14 lg:grid-cols-12 lg:gap-16">
                <Reveal className="flex flex-col gap-4 lg:sticky lg:top-32 lg:col-span-4 lg:self-start">
                    <h2
                        id="home-features-title"
                        className="m-0 font-methaq-display text-methaq-h2 font-bold tracking-[-0.01em] text-balance text-methaq-primary"
                    >
                        كل ما تحتاجينه لدعوة واحدة
                    </h2>
                    <p className="m-0 max-w-[34ch] text-methaq-lead text-pretty text-methaq-muted">
                        من تصميم البطاقة إلى الرابط الذي يصل لضيوفك.
                    </p>
                </Reveal>

                <ul className="m-0 grid list-none gap-x-12 p-0 sm:grid-cols-2 lg:col-span-8">
                    {features.map(
                        ({ icon: Icon, title, description, comingSoon, lead }) => (
                            <li
                                key={title}
                                className={`relative flex flex-col gap-3 border-t border-methaq-gold/30 py-9 ${
                                    lead ? "sm:col-span-2" : ""
                                }`}
                            >
                                <div className="flex items-center gap-4">
                                    <Icon
                                        size={lead ? 30 : 26}
                                        aria-hidden="true"
                                        className="shrink-0 text-methaq-gold"
                                    />
                                    <h3
                                        className={`m-0 font-methaq-display font-bold text-methaq-primary ${
                                            lead
                                                ? "text-[2rem] leading-[1.3]"
                                                : "text-methaq-h3"
                                        }`}
                                    >
                                        {title}
                                    </h3>
                                    {comingSoon && (
                                        <span className="inline-flex items-center gap-1.5 rounded-full border border-methaq-gold/50 px-3 py-0.5 text-[12px] font-semibold text-methaq-gold-dark">
                                            <span
                                                aria-hidden="true"
                                                className="size-1.5 rounded-full bg-methaq-gold motion-safe:animate-methaq-soft-pulse"
                                            />
                                            قريباً
                                        </span>
                                    )}
                                </div>
                                <p
                                    className={`m-0 text-[16px] leading-[1.9] text-methaq-muted ${
                                        lead ? "max-w-[52ch]" : "max-w-[36ch]"
                                    }`}
                                >
                                    {description}
                                </p>
                                {lead && (
                                    <EightPointStar
                                        className="pointer-events-none absolute end-0 top-1/2 hidden size-28 -translate-y-1/2 text-methaq-gold/35 sm:block"
                                    />
                                )}
                            </li>
                        ),
                    )}
                </ul>
            </div>
        </section>
    );
}
