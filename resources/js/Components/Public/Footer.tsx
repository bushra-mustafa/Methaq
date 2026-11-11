import { Link } from "@inertiajs/react";

const footerLinks = [
    { label: "الرئيسية", href: "/" },
    { label: "القوالب", href: "/templates" },
    { label: "من نحن", href: "/about" },
    { label: "تواصل معنا", href: "/contact" },
];

export default function Footer() {
    const year = new Date().getFullYear();

    return (
        <footer className="border-t border-methaq-border bg-methaq-sage">
            <div className="mx-auto max-w-7xl px-6 py-12 lg:px-8">
                <div className="grid gap-10 md:grid-cols-2">
                    <div>
                        <img
                            src="/brand/logos/methaq-horizontal-compact-flat.svg"
                            alt="ميثاق"
                            className="w-36"
                        />

                        <p className="mt-5 max-w-sm text-sm leading-8 text-methaq-muted">
                            ميثاق، مساحة لتصميم دعوات إلكترونية أنيقة تعبّر عن
                            أجمل مناسباتكم.
                        </p>
                    </div>

                    <nav aria-label="روابط التذييل">
                        <h2 className="mb-5 text-lg font-semibold text-methaq-primary">
                            روابط مهمة
                        </h2>

                        <div className="flex flex-col items-start gap-3">
                            {footerLinks.map((item) => (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className="text-sm text-methaq-muted transition-colors hover:text-methaq-primary"
                                >
                                    {item.label}
                                </Link>
                            ))}
                        </div>
                    </nav>
                </div>

                <div className="mt-12 border-t border-methaq-border pt-6 text-center text-xs text-methaq-muted">
                    © {year} ميثاق — جميع الحقوق محفوظة
                </div>
            </div>
        </footer>
    );
}
