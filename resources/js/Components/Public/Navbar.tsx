import { useState } from "react";
import { Link, usePage } from "@inertiajs/react";
import { Menu, X } from "lucide-react";
import type { SharedPageProps } from "../../Types/PageProps";

const navigation = [
    { label: "الرئيسية", href: "/" },
    { label: "القوالب", href: "/templates" },
    { label: "من نحن", href: "/about" },
    { label: "تواصل معنا", href: "/contact" },
];

export default function Navbar() {
    const [menuOpen, setMenuOpen] = useState(false);
    const { auth } = usePage<SharedPageProps>().props;

    return (
        <header className="border-b border-methaq-border bg-methaq-ivory">
            <nav
                className="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-4 lg:px-8"
                aria-label="التنقل الرئيسي"
            >
                <Link href="/" aria-label="ميثاق - الرئيسية">
                    <img
                        src="/brand/logos/methaq-horizontal-compact-flat.svg"
                        alt="ميثاق"
                        className="h-auto w-36 md:w-44"
                    />
                </Link>

                <div className="hidden items-center gap-8 lg:flex">
                    {navigation.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className="text-sm font-medium text-methaq-muted transition-colors hover:text-methaq-primary"
                        >
                            {item.label}
                        </Link>
                    ))}
                </div>

                <div className="hidden items-center gap-3 lg:flex">
                    {auth.user ? (
                        <Link
                            href="/app"
                            className="rounded-methaq-button bg-methaq-primary px-5 py-2.5 text-sm font-medium text-white hover:bg-methaq-primary-hover"
                        >
                            مناسباتي
                        </Link>
                    ) : (
                        <>
                            <Link
                                href="/login"
                                className="text-sm text-methaq-primary"
                            >
                                دخول
                            </Link>
                            <Link
                                href="/register"
                                className="rounded-methaq-button bg-methaq-primary px-5 py-2.5 text-sm font-medium text-white hover:bg-methaq-primary-hover"
                            >
                                ابدئي الآن
                            </Link>
                        </>
                    )}
                </div>

                <button
                    type="button"
                    className="rounded-lg p-2 text-methaq-primary lg:hidden"
                    onClick={() => setMenuOpen((current) => !current)}
                    aria-label={menuOpen ? "إغلاق القائمة" : "فتح القائمة"}
                    aria-expanded={menuOpen}
                    aria-controls="methaq-mobile-menu"
                >
                    {menuOpen ? <X size={24} /> : <Menu size={24} />}
                </button>
            </nav>

            {menuOpen && (
                <nav
                    id="methaq-mobile-menu"
                    className="border-t border-methaq-border px-5 py-4 lg:hidden"
                    aria-label="التنقل عبر الهاتف"
                >
                    <div className="flex flex-col gap-4">
                        {navigation.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                onClick={() => setMenuOpen(false)}
                                className="text-sm text-methaq-ink"
                            >
                                {item.label}
                            </Link>
                        ))}

                        <div className="border-t border-methaq-border pt-4">
                            {auth.user ? (
                                <Link href="/app">مناسباتي</Link>
                            ) : (
                                <div className="flex gap-5">
                                    <Link href="/login">دخول</Link>
                                    <Link href="/register">ابدئي الآن</Link>
                                </div>
                            )}
                        </div>
                    </div>
                </nav>
            )}
        </header>
    );
}
