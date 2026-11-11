import type { PropsWithChildren } from "react";
import Navbar from "../Components/Public/Navbar";
import Footer from "../Components/Public/Footer";

type PublicLayoutProps = PropsWithChildren;

export default function PublicLayout({ children }: PublicLayoutProps) {
    return (
        <div
            className="methaq-public min-h-dvh bg-methaq-ivory font-methaq-body text-methaq-ink"
            dir="rtl"
            lang="ar"
        >
            <a href="#main-content" className="sr-only focus:not-sr-only">
                الانتقال إلى المحتوى
            </a>

            <Navbar />

            <main id="main-content" className="min-h-dvh">
                {children}
            </main>
            <Footer />
        </div>
    );
}
