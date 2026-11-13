import { Check } from "lucide-react";
import HomeEnvelopeShowcase from "../Public/Home/HomeEnvelopeShowcase";
import { EightPointStar } from "../Public/Home/Ornament";
import { authPromises, authPromos } from "./authPromos";
import type { AuthPromoKey } from "./authPromos";

/**
 * The product on show beside the form: the real envelope opening on the dark ink band
 * (on large screens), the headline, and the reasons to try it.
 */
export default function AuthShowcase({ promo }: { promo: AuthPromoKey }) {
    const { heading, body } = authPromos[promo];

    return (
        <aside
            aria-labelledby="auth-promo-title"
            className="relative isolate overflow-hidden bg-methaq-primary px-6 py-14 text-methaq-cream [--methaq-focus-ring:var(--color-methaq-cream)] sm:px-10 lg:flex lg:min-h-dvh lg:items-center lg:px-14 lg:py-12"
        >
            <EightPointStar
                className="pointer-events-none absolute -z-10 size-[44rem] max-w-none text-methaq-gold-light/15"
                style={{ insetInlineStart: "-12rem", top: "-8rem" }}
            />

            <div className="mx-auto flex w-full max-w-xl flex-col gap-8">
                <div className="flex flex-col gap-4">
                    <h2
                        id="auth-promo-title"
                        className="m-0 font-methaq-display text-[clamp(2rem,1.5rem+1.8vw,3rem)] leading-[1.25] font-bold tracking-[-0.01em] text-balance text-methaq-cream motion-safe:animate-methaq-hero-in"
                        style={{ animationDelay: "160ms" }}
                    >
                        {heading}
                    </h2>
                    <p
                        className="m-0 max-w-[44ch] text-methaq-lead text-pretty text-methaq-mist motion-safe:animate-methaq-hero-in"
                        style={{ animationDelay: "280ms" }}
                    >
                        {body}
                    </p>
                </div>

                <div
                    className="hidden lg:block motion-safe:animate-methaq-hero-in"
                    style={{ animationDelay: "400ms" }}
                >
                    <HomeEnvelopeShowcase mode="auto" />
                </div>

                <ul className="m-0 flex list-none flex-col gap-4 border-t border-methaq-gold-light/25 p-0 pt-7">
                    {authPromises.map((promise) => (
                        <li
                            key={promise}
                            className="flex items-start gap-3 text-[16px] leading-[1.9] text-methaq-cream"
                        >
                            <Check
                                size={18}
                                aria-hidden="true"
                                className="mt-1.5 shrink-0 text-methaq-gold-light"
                            />
                            <span>{promise}</span>
                        </li>
                    ))}
                </ul>
            </div>
        </aside>
    );
}
