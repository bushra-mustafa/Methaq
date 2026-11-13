import { useId } from "react";
import type { InputHTMLAttributes, ReactNode } from "react";

export interface AuthFieldProps
    extends Omit<InputHTMLAttributes<HTMLInputElement>, "id"> {
    label: string;
    error?: string;
    hint?: string;
    /** Rendered inside the input's trailing edge (e.g. the show-password button). */
    trailing?: ReactNode;
}

/** Label, input, hint and error as one unit; the single input style for every auth page. */
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
        <div className="flex flex-col gap-2">
            <label
                htmlFor={id}
                className="text-[15px] font-semibold text-methaq-primary"
            >
                {label}
            </label>
            <div className="relative">
                <input
                    id={id}
                    aria-invalid={Boolean(error)}
                    aria-describedby={describedBy}
                    className={`block min-h-13 w-full rounded-methaq-button border bg-white px-4 text-[16px] text-methaq-ink transition-[border-color,box-shadow] duration-200 placeholder:text-methaq-muted/70 focus:border-methaq-primary focus:shadow-[0_0_0_3px_rgba(32,51,34,0.12)] focus:outline-none ${
                        error
                            ? "border-red-700"
                            : "border-methaq-muted/60"
                    } ${trailing ? "pe-14" : ""} ${className}`}
                    {...inputProps}
                />
                {trailing && (
                    <div className="absolute inset-y-0 end-1 flex items-center">
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
                <p
                    id={`${id}-error`}
                    role="alert"
                    className="m-0 text-[14px] font-medium text-red-800"
                >
                    {error}
                </p>
            )}
        </div>
    );
}
