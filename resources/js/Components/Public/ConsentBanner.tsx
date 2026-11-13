import { Link, usePage } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";
import { enableAnalytics } from "../../Domains/Privacy/analytics";
import type { AnalyticsConfig } from "../../Domains/Privacy/analytics";
import { readConsent, writeConsent } from "../../Domains/Privacy/consent";
import type { ConsentChoice } from "../../Domains/Privacy/consent";
import type { SharedPageProps } from "../../Types/PageProps";

export const OPEN_CONSENT_EVENT = "methaq:open-consent";

/**
 * Asks once about optional, cookieless visit statistics on the public pages. Nothing is loaded
 * before she accepts, refusing is as easy as accepting, and the footer lets her change her mind.
 * When the server has no analytics configured the banner never appears: there is nothing to ask.
 */
export default function ConsentBanner() {
    const { analytics } = usePage<SharedPageProps & { analytics: AnalyticsConfig }>().props;
    const [choice, setChoice] = useState<ConsentChoice | null>(null);
    const [open, setOpen] = useState(false);
    const firstButton = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        if (!analytics.enabled) {
            return;
        }

        const saved = readConsent();
        setChoice(saved);
        setOpen(saved === null);
        if (saved === "accepted") {
            enableAnalytics(analytics);
        }

        const reopen = (): void => {
            setChoice(readConsent());
            setOpen(true);
            window.setTimeout(() => firstButton.current?.focus(), 50);
        };
        window.addEventListener(OPEN_CONSENT_EVENT, reopen);

        return () => window.removeEventListener(OPEN_CONSENT_EVENT, reopen);
    }, [analytics]);

    if (!analytics.enabled || !open) {
        return null;
    }

    const decide = (next: ConsentChoice): void => {
        const changedFromAccepted = choice === "accepted" && next === "rejected";
        writeConsent(next);
        setChoice(next);
        setOpen(false);

        if (next === "accepted") {
            enableAnalytics(analytics);
        } else if (changedFromAccepted) {
            window.location.reload();
        }
    };

    return (
        <section
            role="region"
            aria-label="خصوصية القياس الإحصائي"
            className="fixed inset-x-3 bottom-3 z-50 mx-auto flex max-w-3xl flex-col gap-4 rounded-[1rem] border border-methaq-border bg-methaq-cream p-5 shadow-methaq-raised sm:flex-row sm:items-center sm:gap-6"
        >
            <p className="m-0 flex-1 text-[15px] leading-[1.9] text-methaq-ink">
                نريد أن نعرف عدد زوّار صفحاتنا العامة وأي القوالب يُفتح أكثر، بعدّاد بسيط لا يستعمل ملفات تتبع ولا يجمع بياناتك الشخصية.
                موافقتك اختيارية.{" "}
                <Link href="/privacy" className="font-semibold text-methaq-primary underline decoration-methaq-gold/60 underline-offset-4">
                    سياسة الخصوصية
                </Link>
            </p>
            <div className="flex shrink-0 gap-3">
                <button
                    ref={firstButton}
                    type="button"
                    onClick={() => decide("rejected")}
                    className="inline-flex min-h-11 flex-1 items-center justify-center rounded-methaq-button border border-methaq-primary/40 px-6 text-[15px] font-semibold text-methaq-primary transition-colors hover:border-methaq-primary sm:flex-none"
                >
                    أرفض
                </button>
                <button
                    type="button"
                    onClick={() => decide("accepted")}
                    className="inline-flex min-h-11 flex-1 items-center justify-center rounded-methaq-button bg-methaq-primary px-6 text-[15px] font-semibold text-white transition-colors hover:bg-methaq-primary-hover sm:flex-none"
                >
                    أوافق
                </button>
            </div>
        </section>
    );
}
