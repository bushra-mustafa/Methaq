import { ArrowLeft } from "lucide-react";
import type { PropsWithChildren } from "react";

interface AuthSubmitProps {
    processing: boolean;
    pendingLabel: string;
}

/** The one filled action of an auth page; keeps its width while it reports progress. */
export default function AuthSubmit({
    processing,
    pendingLabel,
    children,
}: PropsWithChildren<AuthSubmitProps>) {
    return (
        <button
            type="submit"
            disabled={processing}
            aria-busy={processing}
            className="inline-flex min-h-13 w-full touch-manipulation items-center justify-center gap-3 rounded-methaq-button bg-methaq-primary px-8 text-[16px] font-semibold text-white shadow-methaq-soft transition-[background-color,scale] duration-200 hover:bg-methaq-primary-hover enabled:active:scale-[0.98] disabled:opacity-70 motion-reduce:enabled:active:scale-100"
        >
            {processing ? pendingLabel : children}
            {!processing && <ArrowLeft size={18} aria-hidden="true" />}
        </button>
    );
}
