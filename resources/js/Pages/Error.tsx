import { Head } from "@inertiajs/react";
import SilkBackdrop from "../Components/Auth/SilkBackdrop";
import { EightPointStar } from "../Components/Public/Home/Ornament";

interface ErrorProps {
    status: number;
    title: string;
    message: string;
    homeUrl: string;
}

/**
 * Every error page, from 403 to 503. It is self-contained and links to the main site by absolute
 * address, so it also works on an invitation's own subdomain where site links would not exist.
 */
export default function Error({ status, title, message, homeUrl }: ErrorProps) {
    return (
        <main
            dir="rtl"
            lang="ar"
            className="relative isolate grid min-h-dvh place-items-center overflow-hidden px-6 font-methaq-body text-methaq-cream"
        >
            <Head title={title} />
            <SilkBackdrop />
            <EightPointStar className="pointer-events-none absolute -z-10 size-[34rem] max-w-none text-methaq-gold-light/15" />
            <div className="flex max-w-md flex-col items-center gap-6 text-center motion-safe:animate-methaq-hero-in">
                <p className="m-0 font-methaq-display text-[1.5rem] text-methaq-gold-light" dir="ltr" aria-label={`رمز الخطأ ${status}`}>
                    {status}
                </p>
                <h1 className="m-0 font-methaq-display text-[clamp(2.25rem,1.8rem+2vw,3.25rem)] leading-[1.25] font-bold text-balance text-methaq-cream">
                    {title}
                </h1>
                <p className="m-0 text-[17px] leading-[1.95] text-pretty text-methaq-mist">{message}</p>
                <div className="flex flex-wrap items-center justify-center gap-x-8 gap-y-3">
                    <a
                        href={homeUrl}
                        className="inline-flex min-h-13 items-center justify-center rounded-methaq-button bg-methaq-cream px-8 text-[16px] font-semibold text-methaq-primary transition-colors hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-methaq-gold-light"
                    >
                        العودة إلى ميثاق
                    </a>
                    {status === 419 && (
                        <button
                            type="button"
                            onClick={() => window.location.reload()}
                            className="inline-flex min-h-11 items-center text-[16px] font-medium text-methaq-gold-light underline decoration-methaq-gold-light/50 underline-offset-8 hover:decoration-methaq-gold-light focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-methaq-gold-light"
                        >
                            تحديث الصفحة
                        </button>
                    )}
                </div>
            </div>
        </main>
    );
}
