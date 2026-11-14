import { Link } from "@inertiajs/react";
import { Gift } from "lucide-react";
import { useEffect, useState } from "react";
import { isOpen, remainingLabel } from "../../../Domains/Payments/Services/offerTime";
import type { HomeFreeOfferDTO } from "../../../Types/FreeOffer";
import Reveal from "../Reveal";

interface HomeFreeOfferProps {
    offer: HomeFreeOfferDTO;
    isAuthenticated: boolean;
}

/** Above this many places, dots stop being readable and the number alone is shown. */
const MAXIMUM_DOTS = 12;

/**
 * A limited free offer drawn as a ticket: ivory paper, a gilded edge and two notches. The title leads, the number of
 * places left and the time left sit in the middle, and one filled button closes the row. The countdown updates once a
 * minute (never every second) and disappears on its own when the offer ends.
 */
export default function HomeFreeOffer({ offer, isAuthenticated }: HomeFreeOfferProps) {
    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        const timer = window.setInterval(() => setNow(Date.now()), 60_000);

        return () => window.clearInterval(timer);
    }, []);

    if (!isOpen(offer.endsAt, now)) {
        return null;
    }

    const taken = offer.spotsTotal - offer.spotsLeft;
    const showDots = offer.spotsTotal <= MAXIMUM_DOTS;

    return (
        <section aria-labelledby="home-offer-title" className="px-6 py-10 lg:px-12">
            <Reveal className="relative mx-auto max-w-6xl">
                <div className="relative grid gap-8 rounded-[1.25rem] border border-methaq-gold/45 bg-methaq-cream p-8 shadow-methaq-soft md:grid-cols-[1.5fr_1fr_auto] md:items-center md:gap-10 md:p-10">
                    <span aria-hidden="true" className="absolute start-0 top-1/2 hidden size-6 -translate-y-1/2 translate-x-1/2 rounded-full border border-methaq-gold/45 bg-methaq-ivory md:block" />
                    <span aria-hidden="true" className="absolute end-0 top-1/2 hidden size-6 -translate-y-1/2 -translate-x-1/2 rounded-full border border-methaq-gold/45 bg-methaq-ivory md:block" />

                    <div className="flex flex-col gap-3">
                        <p className="m-0 inline-flex items-center gap-2 text-[15px] font-semibold text-methaq-gold-dark">
                            <Gift size={18} aria-hidden="true" />
                            فرصة محدودة
                        </p>
                        <h2 id="home-offer-title" className="m-0 font-methaq-display text-methaq-h2 font-bold tracking-[-0.01em] text-balance text-methaq-primary">
                            {offer.title}
                        </h2>
                        <p className="m-0 max-w-[48ch] text-[17px] leading-[1.9] text-pretty text-methaq-muted">
                            {offer.description ?? "سجّلي حساباً وصمّمي دعوتك وانشريها دون دفع، ما دامت المقاعد متاحة."}
                            {offer.templateName && <> العرض على قالب «{offer.templateName}».</>}
                        </p>
                    </div>

                    <div className="flex flex-col gap-4 border-methaq-gold/35 md:border-s md:border-dashed md:ps-10">
                        {showDots && (
                            <div aria-hidden="true" className="flex flex-wrap gap-2">
                                {Array.from({ length: offer.spotsTotal }, (_, index) => (
                                    <span
                                        key={index}
                                        className={`size-4 rounded-full border ${index < taken ? "border-methaq-primary bg-methaq-primary" : "border-methaq-gold bg-transparent"}`}
                                    />
                                ))}
                            </div>
                        )}
                        <p className="m-0 text-[17px] text-methaq-ink">
                            بقي <strong className="font-bold tabular-nums">{offer.spotsLeft}</strong> من <span className="tabular-nums">{offer.spotsTotal}</span> مقاعد
                        </p>
                        <p className="m-0 text-[15px] text-methaq-muted">
                            تنتهي بعد <time dateTime={offer.endsAt} className="font-semibold text-methaq-primary tabular-nums">{remainingLabel(offer.endsAt, now)}</time>
                        </p>
                    </div>

                    <div className="flex flex-col gap-3 md:items-center">
                        <Link
                            href={offer.ctaHref}
                            className="inline-flex min-h-13 touch-manipulation items-center justify-center rounded-methaq-button bg-methaq-primary px-9 text-[16px] font-semibold text-methaq-cream transition-[background-color,scale] duration-200 hover:bg-methaq-primary-hover active:scale-[0.96] motion-reduce:active:scale-100"
                        >
                            {isAuthenticated ? "ابدئي مناسبتك المجانية" : "احصلي عليه مجاناً"}
                        </Link>
                        <p className="m-0 max-w-[24ch] text-center text-[13px] leading-[1.7] text-methaq-muted">
                            {isAuthenticated ? "اختاري قالباً ثم فعّلي العرض من صفحة الدفع." : "يكفي حساب مجاني، والمقاعد تُحجز بالأسبقية."}
                        </p>
                    </div>
                </div>
            </Reveal>
        </section>
    );
}
