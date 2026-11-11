import {
    LayoutTemplate,
    Link2,
    ListChecks,
    Mail,
    Music,
    PenLine,
} from "lucide-react";
import type { LucideIcon } from "lucide-react";

interface Feature {
    icon: LucideIcon;
    title: string;
    description: string;
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
            className="px-6 py-20 lg:px-12 lg:py-24"
        >
            <div className="mx-auto flex max-w-7xl flex-col gap-14">
                <div className="mx-auto flex max-w-2xl flex-col gap-3 text-center">
                    <h2
                        id="home-features-title"
                        className="m-0 font-methaq-display text-[clamp(28px,3.4vw,38px)] font-normal text-methaq-primary"
                    >
                        لماذا ميثاق
                    </h2>
                    <p className="m-0 text-[16px] leading-[1.9] text-methaq-muted">
                        كل ما تحتاجينه لدعوة واحدة: من التصميم إلى ردود الضيوف.
                    </p>
                </div>

                <ul className="m-0 grid list-none gap-x-10 gap-y-12 p-0 md:grid-cols-2 lg:grid-cols-3">
                    {features.map(({ icon: Icon, title, description }) => (
                        <li
                            key={title}
                            className="flex flex-col gap-3 border-t border-methaq-gold/30 pt-6 text-right"
                        >
                            <Icon
                                size={24}
                                aria-hidden="true"
                                className="text-methaq-gold-dark"
                            />
                            <h3 className="m-0 text-[21px] font-normal text-methaq-primary">
                                {title}
                            </h3>
                            <p className="m-0 max-w-[38ch] text-[15px] leading-[1.9] text-methaq-muted">
                                {description}
                            </p>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}
