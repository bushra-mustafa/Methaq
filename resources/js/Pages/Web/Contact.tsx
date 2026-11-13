import { Head, Link } from "@inertiajs/react";
import PageIntro from "../../Components/Public/PageIntro";
import Reveal from "../../Components/Public/Reveal";
import PublicLayout from "../../Layouts/PublicLayout";

const shortcuts = [
    { href: "/templates", title: "تتصفحين القوالب؟", text: "شاهدي كل الاتجاهات المتاحة واختاري بدايتك." },
    { href: "/privacy", title: "تسألين عن بياناتك؟", text: "نوضّح ما نجمعه ولماذا ومن يراه." },
    { href: "/login", title: "عندك حساب؟", text: "ادخلي لمتابعة مناسباتك وحالة الدفع." },
];

export default function Contact() {
    return (
        <PublicLayout>
            <Head title="تواصل معنا">
                <meta name="description" content="قنوات التواصل الرسمية مع ميثاق ستُضاف عند اعتمادها." />
            </Head>
            <PageIntro
                title="يسعدنا تواصلك معنا"
                lead="ستُضاف قنوات التواصل الرسمية هنا عند اعتمادها. إلى ذلك الحين، هذه أسرع الطرق للوصول لما تبحثين عنه."
            />
            <section aria-label="روابط سريعة" className="px-6 pb-methaq-section lg:px-12">
                <ul className="mx-auto m-0 grid max-w-7xl list-none gap-x-12 p-0 md:grid-cols-3">
                    {shortcuts.map((shortcut, index) => (
                        <Reveal as="li" key={shortcut.href} delay={index * 100} className="border-t border-methaq-gold/30 py-8">
                            <Link href={shortcut.href} className="group flex flex-col gap-3 rounded-methaq-button">
                                <h2 className="m-0 font-methaq-display text-methaq-h3 font-bold text-methaq-primary underline decoration-transparent decoration-1 underline-offset-8 transition-[text-decoration-color] group-hover:decoration-methaq-gold">
                                    {shortcut.title}
                                </h2>
                                <p className="m-0 text-[17px] leading-[1.9] text-methaq-muted">{shortcut.text}</p>
                            </Link>
                        </Reveal>
                    ))}
                </ul>
            </section>
        </PublicLayout>
    );
}
