import { useEffect, useRef, useState } from "react";
import { Link, usePage } from "@inertiajs/react";
import { Menu, PenLine, X } from "lucide-react";
import type { SharedPageProps } from "../../Types/PageProps";

interface NavigationItem {
    label: string;
    href: string;
    /** Home-page sections are plain anchors so the browser scrolls to them. */
    section?: boolean;
}

/** Same labels and order as the Stitch home navigation. */
const navigation: NavigationItem[] = [
    { label: "الرئيسية", href: "/" },
    { label: "معرض القوالب", href: "/templates" },
    { label: "كيف يعمل ميثاق", href: "/#home-steps", section: true },
    { label: "تجربة فتح الظرف", href: "/#home-envelope", section: true },
    { label: "المزايا", href: "/#home-features", section: true },
    { label: "من نحن", href: "/about" },
];

function NavigationLink({
    item,
    current,
    className,
    onNavigate,
}: {
    item: NavigationItem;
    current: boolean;
    className: string;
    onNavigate?: () => void;
}) {
    const props = {
        href: item.href,
        "aria-current": current ? ("page" as const) : undefined,
        className,
        onClick: onNavigate,
    };

    return item.section ? (
        <a {...props}>{item.label}</a>
    ) : (
        <Link {...props}>{item.label}</Link>
    );
}

export default function Navbar() {
    const [menuOpen, setMenuOpen] = useState(false);
    const toggle = useRef<HTMLButtonElement>(null);
    const page = usePage<SharedPageProps>();
    const { auth } = page.props;
    const currentPath = page.url.split("?")[0];
    const ctaHref = auth.user ? "/app" : "/register";
    const ctaLabel = auth.user ? "مناسباتي" : "صمّمي دعوتك";

    useEffect(() => {
        if (!menuOpen) {
            return;
        }
        const closeOnEscape = (event: KeyboardEvent): void => {
            if (event.key === "Escape") {
                setMenuOpen(false);
                toggle.current?.focus();
            }
        };
        document.addEventListener("keydown", closeOnEscape);

        return () => document.removeEventListener("keydown", closeOnEscape);
    }, [menuOpen]);

    return (
        <header className="sticky top-0 z-50 border-b border-methaq-border bg-methaq-ivory/90 backdrop-blur-md">
            <nav
                className="mx-auto flex h-20 max-w-7xl items-center justify-between gap-6 px-6 lg:px-12"
                aria-label="التنقل الرئيسي"
            >
                <Link href="/" aria-label="ميثاق - الرئيسية">
                    <img
                        src="/brand/logos/methaq-horizontal-compact-flat.svg"
                        alt="ميثاق"
                        width={688}
                        height={150}
                        className="h-auto w-36 md:w-44"
                    />
                </Link>

                <div className="hidden items-center gap-8 xl:flex">
                    {navigation.map((item) => {
                        const current = !item.section && currentPath === item.href;

                        return (
                            <NavigationLink
                                key={item.href}
                                item={item}
                                current={current}
                                className={`border-b-2 pb-1 text-[15px] transition-colors ${
                                    current
                                        ? "border-methaq-gold font-semibold text-methaq-primary"
                                        : "border-transparent font-medium text-methaq-muted hover:text-methaq-primary"
                                }`}
                            />
                        );
                    })}
                </div>

                <div className="hidden items-center gap-4 xl:flex">
                    {!auth.user && (
                        <Link
                            href="/login"
                            className="text-[14px] font-medium text-methaq-primary hover:underline hover:underline-offset-4"
                        >
                            دخول
                        </Link>
                    )}
                    <Link
                        href={ctaHref}
                        className="inline-flex items-center gap-2 rounded-methaq-button border border-methaq-gold-light/40 bg-methaq-primary px-5 py-2.5 text-[14px] font-medium text-methaq-cream shadow-sm transition-[background-color,box-shadow] duration-300 hover:bg-methaq-primary-hover hover:shadow-md"
                    >
                        <PenLine
                            size={18}
                            aria-hidden="true"
                            className="text-methaq-gold-light"
                        />
                        {ctaLabel}
                    </Link>
                </div>

                <button
                    ref={toggle}
                    type="button"
                    className="grid size-11 place-items-center rounded-lg text-methaq-primary xl:hidden"
                    onClick={() => setMenuOpen((current) => !current)}
                    aria-label={menuOpen ? "إغلاق القائمة" : "فتح القائمة"}
                    aria-expanded={menuOpen}
                    aria-controls="methaq-mobile-menu"
                >
                    {menuOpen ? <X size={24} aria-hidden="true" /> : <Menu size={24} aria-hidden="true" />}
                </button>
            </nav>

            {menuOpen && (
                <nav
                    id="methaq-mobile-menu"
                    className="border-t border-methaq-border bg-methaq-ivory px-6 py-4 xl:hidden"
                    aria-label="التنقل عبر الهاتف"
                >
                    <div className="flex flex-col gap-4">
                        {navigation.map((item) => {
                            const current =
                                !item.section && currentPath === item.href;

                            return (
                                <NavigationLink
                                    key={item.href}
                                    item={item}
                                    current={current}
                                    onNavigate={() => setMenuOpen(false)}
                                    className={`text-[15px] font-medium transition-colors ${
                                        current
                                            ? "text-methaq-primary"
                                            : "text-methaq-muted hover:text-methaq-primary"
                                    }`}
                                />
                            );
                        })}

                        <div className="flex items-center gap-5 border-t border-methaq-border pt-4">
                            {!auth.user && (
                                <Link
                                    href="/login"
                                    className="text-[15px] text-methaq-primary"
                                >
                                    دخول
                                </Link>
                            )}
                            <Link
                                href={ctaHref}
                                className="inline-flex items-center gap-2 rounded-methaq-button bg-methaq-primary px-4 py-2 text-[14px] font-medium text-methaq-cream"
                            >
                                <PenLine
                                    size={16}
                                    aria-hidden="true"
                                    className="text-methaq-gold-light"
                                />
                                {ctaLabel}
                            </Link>
                        </div>
                    </div>
                </nav>
            )}
        </header>
    );
}
