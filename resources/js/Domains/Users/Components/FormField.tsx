import type { InputHTMLAttributes } from 'react';

interface FormFieldProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'id'> {
    label: string;
    error?: string;
}

export function FormField({ label, error, name, ...inputProps }: FormFieldProps) {
    const inputId = `field-${name ?? label}`;
    const errorId = `${inputId}-error`;

    return <label className="account-field" htmlFor={inputId}>
        <span>{label}</span>
        <input id={inputId} name={name} aria-invalid={Boolean(error)} aria-describedby={error ? errorId : undefined} {...inputProps} />
        {error && <small id={errorId} role="alert">{error}</small>}
    </label>;
}
