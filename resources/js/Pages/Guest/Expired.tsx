import { Head } from "@inertiajs/react";

/** Shown on an invitation link after it expires. It carries no event or guest data. */
interface ExpiredProps {
    homeUrl: string;
}

export default function Expired({ homeUrl }: ExpiredProps) {
    return (
        <main
            dir="rtl"
            lang="ar"
            className="grid min-h-dvh place-items-center bg-methaq-ivory px-6 font-methaq-body text-methaq-ink"
        >
            <Head title="انتهت صلاحية الدعوة" />
            <div className="flex max-w-md flex-col items-center gap-5 text-center">
                <img
                    src="/brand/logos/methaq-symbol-small-flat.svg"
                    alt=""
                    width={56}
                    height={56}
                    className="opacity-80"
                />
                <h1 className="m-0 font-methaq-display text-[32px] font-bold text-methaq-primary">
                    انتهت صلاحية هذه الدعوة
                </h1>
                <p className="m-0 text-[16px] leading-[1.9] text-methaq-muted">
                    رابط الدعوة لم يعد متاحاً بعد موعد المناسبة. شكراً لاهتمامكم،
                    ونتمنى لأصحاب الفرح دوام المسرّات.
                </p>
                <a
                    href={homeUrl}
                    className="text-[14px] font-medium text-methaq-gold-dark underline underline-offset-4"
                >
                    ميثاق · دعوات رقمية
                </a>
            </div>
        </main>
    );
}
