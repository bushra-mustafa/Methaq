import { useId } from "react";
import type { InputHTMLAttributes, ReactNode } from "react";

export interface AuthFieldProps
    extends Omit<InputHTMLAttributes<HTMLInputElement>, "id"> {
    label: string;
    error?: string;
    hint?: string;
    /** Rendered at the input's trailing edge (e.g. the show-password button). */
    trailing?: ReactNode;
}

/** A line to write on: label above, ink-coloured text, a rule that darkens when focused. */
export default function AuthField({
    label,
    error,
    hint,
    trailing,
    className = "",
    ...inputProps
}: AuthFieldProps) {
    const id = useId();
    const describedBy =
        [error ? `${id}-error` : null, hint ? `${id}-hint` : null]
            .filter(Boolean)
            .join(" ") || undefined;

    return (
        <div className="flex flex-col gap-1">
            <label htmlFor={id} className="text-[14px] font-semibold text-methaq-gold-dark">
                {label}
            </label>
            <div className="relative">
                <input
                    id={id}
                    aria-invalid={Boolean(error)}
                    aria-describedby={describedBy}
                    className={`peer block min-h-12 w-full rounded-t-[0.25rem] border-0 border-b bg-transparent px-1 text-[18px] text-methaq-ink transition-[background-color,box-shadow] duration-200 focus:bg-methaq-gold-soft/25 focus:outline-none ${
                        error
                            ? "border-red-700 shadow-[0_1px_0_0_#b91c1c]"
                            : "border-methaq-muted/60 focus:shadow-[0_1px_0_0_var(--color-methaq-primary)] focus:border-methaq-primary"
                    } ${trailing ? "pe-12" : ""} ${className}`}
                    {...inputProps}
                />
                {trailing && (
                    <div className="absolute inset-y-0 end-0 flex items-center">
                        {trailing}
                    </div>
                )}
            </div>
            {hint && !error && (
                <p id={`${id}-hint`} className="m-0 text-[14px] text-methaq-muted">
                    {hint}
                </p>
            )}
            {error && (
                <p id={`${id}-error`} role="alert" className="m-0 text-[14px] font-medium text-red-800">
                    {error}
                </p>
            )}
        </div>
    );
}
