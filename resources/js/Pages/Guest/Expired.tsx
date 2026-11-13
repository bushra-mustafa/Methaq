import { Head } from "@inertiajs/react";
import SilkBackdrop from "../../Components/Auth/SilkBackdrop";
import { EightPointStar } from "../../Components/Public/Home/Ornament";

/** Shown on an invitation link after it expires. It carries no event or guest data. */
interface ExpiredProps {
    homeUrl: string;
}

export default function Expired({ homeUrl }: ExpiredProps) {
    return (
        <main
            dir="rtl"
            lang="ar"
            className="relative isolate grid min-h-dvh place-items-center overflow-hidden px-6 font-methaq-body text-methaq-cream"
        >
            <Head title="انتهت صلاحية الدعوة" />
            <SilkBackdrop />
            <EightPointStar className="pointer-events-none absolute -z-10 size-[34rem] max-w-none text-methaq-gold-light/15" />
            <div className="flex max-w-md flex-col items-center gap-6 text-center motion-safe:animate-methaq-hero-in">
                <h1 className="m-0 font-methaq-display text-[clamp(2.25rem,1.8rem+2vw,3.25rem)] leading-[1.25] font-bold text-balance text-methaq-cream">
                    انتهت صلاحية هذه الدعوة
                </h1>
                <p className="m-0 text-[17px] leading-[1.95] text-pretty text-methaq-mist">
                    رابط الدعوة لم يعد متاحاً بعد موعد المناسبة. شكراً لاهتمامكم، ونتمنى لأصحاب الفرح دوام المسرّات.
                </p>
                <a
                    href={homeUrl}
                    className="inline-flex min-h-11 items-center text-[16px] font-medium text-methaq-gold-light underline decoration-methaq-gold-light/50 underline-offset-8 transition-[text-decoration-color] hover:decoration-methaq-gold-light focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-methaq-gold-light"
                >
                    ميثاق · دعوات رقمية
                </a>
            </div>
        </main>
    );
}
