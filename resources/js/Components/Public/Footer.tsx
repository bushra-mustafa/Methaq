import { Link, usePage } from "@inertiajs/react";
import type { SharedPageProps } from "../../Types/PageProps";

const footerLinks = [
    { label: "الرئيسية", href: "/" },
    { label: "القوالب", href: "/templates" },
    { label: "من نحن", href: "/about" },
    { label: "تواصل معنا", href: "/contact" },
    { label: "سياسة الخصوصية", href: "/privacy" },
];

export default function Footer() {
    const year = new Date().getFullYear();
    const { auth } = usePage<SharedPageProps>().props;

    return (
        <footer className="border-t border-methaq-border bg-methaq-sage">
            <div className="mx-auto max-w-7xl px-6 py-14 lg:px-12">
                <div className="grid gap-12 md:grid-cols-12">
                    <div className="md:col-span-5">
                        <img
                            src="/brand/logos/methaq-horizontal-compact-flat.svg"
                            alt="ميثاق"
                            className="w-40"
                        />

                        <p className="mt-5 max-w-sm text-[15px] leading-8 text-methaq-muted">
                            ميثاق، مساحة لتصميم دعوات إلكترونية أنيقة تعبّر عن
                            أجمل مناسباتكم.
                        </p>
                    </div>

                    <nav aria-label="روابط التذييل" className="md:col-span-3">
                        <p className="mb-5 font-methaq-display text-[20px] font-bold text-methaq-primary">
                            روابط مهمة
                        </p>

                        <ul className="m-0 flex list-none flex-col items-start gap-1 p-0">
                            {footerLinks.map((item) => (
                                <li key={item.href}>
                                    <Link
                                        href={item.href}
                                        className="inline-flex min-h-11 items-center text-[15px] text-methaq-muted transition-colors hover:text-methaq-primary"
                                    >
                                        {item.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </nav>

                    <div className="flex flex-col items-start gap-4 md:col-span-4">
                        <p className="m-0 font-methaq-display text-[20px] font-bold text-methaq-primary">
                            ابدئي دعوتك اليوم
                        </p>
                        <p className="m-0 max-w-[30ch] text-[15px] leading-8 text-methaq-muted">
                            اختاري قالباً وغيّري فيه ما تشائين، ثم شاركي الرابط.
                        </p>
                        <Link
                            href={auth.user ? "/app" : "/register"}
                            className="inline-flex min-h-12 items-center rounded-methaq-button bg-methaq-primary px-6 text-[15px] font-semibold text-methaq-cream transition-colors hover:bg-methaq-primary-hover"
                        >
                            {auth.user ? "مناسباتي" : "صمّمي دعوتك"}
                        </Link>
                    </div>
                </div>

                <div className="mt-14 flex flex-col items-center justify-between gap-3 border-t border-methaq-border pt-6 text-center sm:flex-row">
                    <p className="m-0 font-methaq-display text-[18px] text-methaq-primary">
                        «لكل حكاية جميلة... ميثاقٌ يليق بها»
                    </p>

                    <p className="m-0 text-[13px] text-methaq-muted">
                        © {year} ميثاق، جميع الحقوق محفوظة
                    </p>
                </div>
            </div>
        </footer>
    );
}
