import { Head, Link } from "@inertiajs/react";
import PublicLayout from "../../Layouts/PublicLayout";
import Reveal from "../../Components/Public/Reveal";

interface PolicySection {
    title: string;
    paragraphs: string[];
}

const LAST_UPDATED = "12/11/2026";

const sections: PolicySection[] = [
    {
        title: "البيانات التي نجمعها",
        paragraphs: [
            "بيانات الحساب: الاسم والبريد الإلكتروني وكلمة المرور (تُحفظ مشفّرة ولا يمكن قراءتها).",
            "بيانات المناسبة: عنوانها وتاريخها وتصميم البطاقة والظرف والموسيقى التي تختارينها.",
            "ردود الضيوف عند تأكيد الحضور: الاسم، ورقم الهاتف إن أُدخل، والحضور أو الاعتذار، وعدد المرافقين، والملاحظة إن وُجدت.",
            "ملفات تعريف الارتباط الضرورية فقط لتسجيل الدخول وحماية النماذج.",
        ],
    },
    {
        title: "كيف نستخدمها",
        paragraphs: [
            "لتشغيل حسابك، وحفظ تصاميمك، ونشر دعوتك على الرابط الخاص بمناسبتك، وإيصال ردود ضيوفك إليك.",
            "لحماية المنصة من الاستخدام المسيء، ولسجلات الإدارة اللازمة لأمان الخدمة.",
            "لا نبيع بياناتك ولا بيانات ضيوفك لأي طرف.",
        ],
    },
    {
        title: "عرض التصاميم كأمثلة",
        paragraphs: [
            "قد نستخدم تصاميم الدعوات التي تُنشأ على ميثاق كأمثلة في الموقع وفي التعريف بالمنصة.",
            "قبل أي عرض نزيل الأسماء والتواريخ والأماكن وأرقام الهواتف وكل ما يدل على أصحاب المناسبة أو ضيوفهم، فيُعرض التصميم وحده.",
            "إن لم ترغبي في عرض تصميمك، راسلينا من صفحة التواصل وسنستثنيه.",
        ],
    },
    {
        title: "من يرى دعوتك",
        paragraphs: [
            "مسودتك خاصة بك. يرى الضيوف النسخة المنشورة فقط، وعلى الرابط الخاص بمناسبتك، وتتوقف الدعوة عن الظهور بعد انتهاء مدتها.",
        ],
    },
    {
        title: "حقوقك",
        paragraphs: [
            "يمكنك تعديل بيانات حسابك من صفحة الملف الشخصي، وطلب حذف حسابك أو بياناتك بمراسلتنا.",
        ],
    },
];

export default function Privacy() {
    return (
        <PublicLayout>
            <Head title="سياسة الخصوصية" />
            <article className="mx-auto flex max-w-3xl flex-col gap-12 px-6 py-20 text-right">
                <Reveal as="header" className="flex flex-col gap-3">
                    <span className="text-[13px] font-semibold tracking-wider text-methaq-gold-dark">
                        آخر تحديث {LAST_UPDATED}
                    </span>
                    <h1 className="m-0 font-methaq-display text-[40px] font-bold text-methaq-primary md:text-[48px]">
                        سياسة الخصوصية
                    </h1>
                    <p className="m-0 text-[17px] leading-[1.9] text-methaq-muted">
                        نوضّح هنا ما نجمعه عند استخدامك لميثاق، ولماذا، ومن
                        يراه.
                    </p>
                </Reveal>

                {sections.map((section, index) => (
                    <Reveal
                        as="section"
                        key={section.title}
                        delay={index * 60}
                        className="flex flex-col gap-4 border-t border-methaq-gold/25 pt-8"
                    >
                        <h2 className="m-0 font-methaq-display text-[26px] font-bold text-methaq-primary">
                            {section.title}
                        </h2>
                        <ul className="m-0 flex list-disc flex-col gap-2 ps-5 text-[16px] leading-[1.9] text-methaq-ink marker:text-methaq-gold">
                            {section.paragraphs.map((paragraph) => (
                                <li key={paragraph}>{paragraph}</li>
                            ))}
                        </ul>
                    </Reveal>
                ))}

                <Reveal as="p" className="m-0 text-[16px] leading-[1.9] text-methaq-muted">
                    لأي سؤال عن خصوصيتك،{" "}
                    <Link
                        href="/contact"
                        className="text-methaq-primary underline decoration-methaq-gold/60 underline-offset-4"
                    >
                        تواصلي معنا
                    </Link>
                    .
                </Reveal>
            </article>
        </PublicLayout>
    );
}
