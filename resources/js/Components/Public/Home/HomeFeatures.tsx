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

interface Feature {
    icon: LucideIcon;
    title: string;
    description: string;
    comingSoon?: boolean;
}

const features: Feature[] = [
    {
        icon: PenLine,
        title: "محرر بطاقة مرئي",
        description:
            "غيّري النصوص والألوان والخطوط، ورتّبي عناصر البطاقة كما تحبين.",
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
        icon: ListChecks,
        title: "تأكيد الحضور",
        description:
            "يرد ضيوفك بالحضور أو الاعتذار وعدد المرافقين دون إنشاء حساب.",
        comingSoon: true,
    },
    {
        icon: LayoutTemplate,
        title: "قوالب لكل مناسبة",
        description: "زفاف وحنّة وعقد قران وتخرج، ولكل قالب ألوانه وزخارفه.",
    },
];

export default function HomeFeatures() {
    return (
        <section
            aria-labelledby="home-features-title"
            className="px-6 py-24 lg:px-12"
        >
            <div className="mx-auto flex max-w-7xl flex-col gap-16">
                <Reveal className="mx-auto flex max-w-2xl flex-col gap-3 text-center">
                    <span className="text-[13px] font-semibold tracking-wider text-methaq-gold-dark">
                        لماذا ميثاق؟
                    </span>
                    <h2
                        id="home-features-title"
                        className="m-0 font-methaq-display text-[34px] font-bold text-methaq-primary md:text-[40px]"
                    >
                        كل ما تحتاجينه لدعوة واحدة
                    </h2>
                    <p className="m-0 text-[16px] leading-relaxed text-methaq-muted">
                        من تصميم البطاقة إلى الرابط الذي يصل لضيوفك.
                    </p>
                </Reveal>

                <ul className="m-0 grid list-none grid-cols-1 gap-8 p-0 md:grid-cols-2 lg:grid-cols-3">
                    {features.map(
                        ({ icon: Icon, title, description, comingSoon }, index) => (
                            <Reveal
                                as="li"
                                key={title}
                                delay={(index % 3) * 110}
                            >
                                <div className="group flex h-full flex-col gap-4 rounded-methaq-card border border-methaq-border bg-methaq-cream p-8 text-right transition-[translate,box-shadow] duration-400 ease-[cubic-bezier(0.16,1,0.3,1)] hover:-translate-y-1.5 hover:shadow-[0_18px_30px_-10px_rgba(139,106,44,0.16),0_6px_14px_-4px_rgba(7,75,54,0.08)] motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                                <div className="flex items-center justify-between">
                                    <span className="grid size-12 place-items-center rounded-lg bg-methaq-surface text-methaq-gold-dark shadow-sm transition-transform duration-500 group-hover:-rotate-6 group-hover:scale-110 motion-reduce:transition-none motion-reduce:group-hover:rotate-0 motion-reduce:group-hover:scale-100">
                                        <Icon size={24} aria-hidden="true" />
                                    </span>
                                    {comingSoon && (
                                        <span className="inline-flex items-center gap-1.5 rounded-full border border-methaq-gold/40 bg-methaq-surface px-3 py-1 text-[12px] font-semibold text-methaq-gold-dark">
                                            <span
                                                aria-hidden="true"
                                                className="size-1.5 rounded-full bg-methaq-gold motion-safe:animate-methaq-soft-pulse"
                                            />
                                            قريباً
                                        </span>
                                    )}
                                </div>
                                <h3 className="m-0 font-methaq-display text-[22px] font-bold text-methaq-primary">
                                    {title}
                                </h3>
                                <p className="m-0 text-[15px] leading-relaxed text-methaq-muted">
                                    {description}
                                </p>
                                </div>
                            </Reveal>
                        ),
                    )}
                </ul>
            </div>
        </section>
    );
}
