import { Head, Link, usePage } from "@inertiajs/react";
import ContactChannelList from "../../Components/Public/ContactChannelList";
import PageIntro from "../../Components/Public/PageIntro";
import Reveal from "../../Components/Public/Reveal";
import PublicLayout from "../../Layouts/PublicLayout";
import type { SharedPageProps } from "../../Types/PageProps";
import type { ContactChannelDTO } from "../../Types/Support";

const shortcuts = [
    { href: "/templates", title: "تتصفحين القوالب؟", text: "شاهدي كل الاتجاهات المتاحة واختاري بدايتك." },
    { href: "/privacy", title: "تسألين عن بياناتك؟", text: "نوضّح ما نجمعه ولماذا ومن يراه." },
    { href: "/login", title: "عندك حساب؟", text: "ادخلي لمتابعة مناسباتك وحالة الدفع." },
];

export default function Contact({ channels }: { channels: ContactChannelDTO[] }) {
    const { auth } = usePage<SharedPageProps>().props;

    return (
        <PublicLayout>
            <Head title="تواصل معنا">
                <meta name="description" content="راسلي فريق ميثاق أو تواصلي معنا عبر البريد وقنواتنا الرسمية." />
            </Head>
            <PageIntro
                title="يسعدنا تواصلك معنا"
                lead="عندك مشكلة أو فكرة لتصميم مخصص؟ اكتبي لنا، ونرد عليك داخل حسابك."
            />
            <section aria-labelledby="contact-write" className="px-6 pb-12 lg:px-12">
                <div className="mx-auto grid max-w-7xl gap-8 border-t border-methaq-gold/30 pt-8 md:grid-cols-2">
                    <div className="flex flex-col items-start gap-4">
                        <h2 id="contact-write" className="m-0 font-methaq-display text-methaq-h3 font-bold text-methaq-primary">راسلي الفريق</h2>
                        <p className="m-0 text-[17px] leading-[1.9] text-methaq-muted">
                            اختاري «عرض مشكلة» أو «طلب تصميم مخصص»، واكتبي رسالتك، وسيصلك الرد في صفحة الرسائل من حسابك.
                        </p>
                        <Link
                            href={auth.user ? "/app/support" : "/login"}
                            className="inline-flex min-h-12 items-center rounded-methaq-button bg-methaq-primary px-6 text-[15px] font-semibold text-methaq-cream transition-colors hover:bg-methaq-primary-hover"
                        >
                            {auth.user ? "اكتبي رسالتك" : "ادخلي لتكتبي رسالتك"}
                        </Link>
                    </div>
                    {channels.length > 0 && (
                        <div className="flex flex-col items-start gap-4">
                            <h2 className="m-0 font-methaq-display text-methaq-h3 font-bold text-methaq-primary">قنواتنا</h2>
                            <ContactChannelList channels={channels} />
                        </div>
                    )}
                </div>
            </section>
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
