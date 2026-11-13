import type { PropsWithChildren } from "react";

/** A polite confirmation shown above a form (link sent, session notice). */
export default function AuthStatus({ children }: PropsWithChildren) {
    return (
        <p
            role="status"
            className="m-0 mb-6 rounded-methaq-button border border-methaq-gold/40 bg-methaq-gold-soft/40 px-4 py-3 text-[15px] leading-[1.8] text-methaq-ink"
        >
            {children}
        </p>
    );
}
