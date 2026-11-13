import { Head, Link } from "@inertiajs/react";
import PageIntro from "../../Components/Public/PageIntro";
import Reveal from "../../Components/Public/Reveal";
import PublicLayout from "../../Layouts/PublicLayout";

const principles = [
    { title: "عربية من أول سطر", text: "الكتابة من اليمين، والخطوط العربية، والأسماء والتواريخ كما تُكتب في مناسباتنا، لا ترجمة لقالب أجنبي." },
    { title: "دعوتك على طريقتك", text: "اختاري قالباً ثم غيّري نصوصه وألوانه وخطوطه وعناصره، أو ابدئي من بطاقة فارغة." },
    { title: "لحظة فتح يتذكرها ضيوفك", text: "رابط واحد يفتحه الضيف كظرف حقيقي بختم ميثاق، فتنكشف البطاقة على موسيقى تختارينها." },
    { title: "دفع بسيط وواضح", text: "تصمّمين وتجرّبين دون دفع، وعند النشر تحوّلين المبلغ بنكياً وترفعين القسيمة، فنراجعها ونفعّل دعوتك." },
];

export default function About() {
    return (
        <PublicLayout>
            <Head title="من نحن">
                <meta name="description" content="ميثاق منصة عربية لصناعة الدعوات الإلكترونية بظرف يُفتح، تصمّمينها وتشاركينها برابط واحد." />
            </Head>
            <PageIntro
                title="ميثاق: دعوات تُفتح كما يُفتح الظرف"
                lead="منصة عربية لصناعة الدعوات الإلكترونية. تصمّمين بطاقتك، وتشاركينها برابط واحد، ويفتحها ضيوفك كظرف حقيقي."
            />
            <section aria-label="ما نؤمن به" className="px-6 pb-methaq-section lg:px-12">
                <ul className="mx-auto m-0 grid max-w-7xl list-none gap-x-16 p-0 md:grid-cols-2">
                    {principles.map((principle, index) => (
                        <Reveal as="li" key={principle.title} delay={(index % 2) * 100} className="flex flex-col gap-3 border-t border-methaq-gold/30 py-8">
                            <h2 className="m-0 font-methaq-display text-methaq-h3 font-bold text-methaq-primary">{principle.title}</h2>
                            <p className="m-0 max-w-[46ch] text-[17px] leading-[1.95] text-pretty text-methaq-muted">{principle.text}</p>
                        </Reveal>
                    ))}
                </ul>
                <div className="mx-auto mt-6 flex max-w-7xl flex-wrap items-center gap-x-8 gap-y-4">
                    <Link href="/register" className="inline-flex min-h-13 items-center justify-center rounded-methaq-button bg-methaq-primary px-8 text-[16px] font-semibold text-white shadow-methaq-soft transition-colors hover:bg-methaq-primary-hover">
                        صمّمي دعوتك
                    </Link>
                    <Link href="/templates" className="inline-flex min-h-11 items-center text-[16px] font-medium text-methaq-primary underline decoration-methaq-gold/60 underline-offset-8 hover:decoration-methaq-gold">
                        تصفّحي القوالب
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
