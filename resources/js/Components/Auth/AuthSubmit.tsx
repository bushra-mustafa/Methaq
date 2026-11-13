import type { PropsWithChildren } from "react";
import WaxSeal from "./WaxSeal";

/** The one action of an auth page: a wax seal you press, one short word on it. */
export default function AuthSubmit({
    processing,
    children,
}: PropsWithChildren<{ processing: boolean }>) {
    return (
        <button
            type="submit"
            disabled={processing}
            aria-busy={processing}
            className="mx-auto mt-2 block size-28 touch-manipulation rounded-full transition-[scale,rotate,filter] duration-200 enabled:hover:-rotate-6 enabled:hover:brightness-105 enabled:active:scale-[0.92] disabled:animate-methaq-seal-pulse disabled:brightness-90 motion-safe:animate-methaq-stamp motion-reduce:enabled:active:scale-100 motion-reduce:enabled:hover:rotate-0"
            style={{ animationDelay: "550ms" }}
        >
            <WaxSeal>{children}</WaxSeal>
            {processing && <span className="sr-only">جاري المعالجة…</span>}
        </button>
    );
}
