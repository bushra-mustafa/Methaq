import type { PropsWithChildren } from "react";
import Navbar from "../Components/Public/Navbar";
import ConsentBanner from "../Components/Public/ConsentBanner";
import Footer from "../Components/Public/Footer";

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
                className="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[60] focus:rounded-methaq-button focus:bg-methaq-primary focus:px-5 focus:py-3 focus:text-[15px] focus:font-semibold focus:text-methaq-cream"
            >
                الانتقال إلى المحتوى
            </a>

            <Navbar />

            <main id="main-content" tabIndex={-1} className="min-h-dvh outline-none">
                {children}
            </main>
            <Footer />
            <ConsentBanner />
        </div>
    );
}
