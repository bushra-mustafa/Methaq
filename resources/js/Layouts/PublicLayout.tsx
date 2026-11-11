import type { PropsWithChildren } from "react";

type PublicLayoutProps = PropsWithChildren;

export default function PublicLayout({ children }: PublicLayoutProps) {
    return (
        <div
            className="methaq-public min-h-dvh bg-methaq-ivory font-methaq-body text-methaq-ink"
            dir="rtl"
            lang="ar"
        >
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-white focus:p-3"
            >
                الانتقال إلى المحتوى
            </a>

            <main id="main-content" className="min-h-dvh">
                {children}
            </main>
        </div>
    );
}
