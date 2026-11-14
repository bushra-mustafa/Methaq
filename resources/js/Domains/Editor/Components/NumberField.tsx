import { useState } from 'react';
import { finalValue, isNumberText, liveValue } from '../Services/numberInput';

interface NumberFieldProps {
    label: string;
    value: number;
    minimum: number;
    maximum: number;
    onCommit: (value: number) => void;
}

/** A number box that lets the person type freely, applies a value as soon as it is valid and fixes it when they leave. */
export function NumberField({ label, value, minimum, maximum, onCommit }: NumberFieldProps) {
    const [draft, setDraft] = useState<string | null>(null);
    const finish = (): void => {
        if (draft === null) return;
        const next = finalValue(draft, minimum, maximum, value);
        if (next !== value) onCommit(next);
        setDraft(null);
    };

    return <label><span>{label}</span><input
        type="text"
        inputMode="decimal"
        dir="ltr"
        value={draft ?? String(value)}
        onFocus={(event) => event.currentTarget.select()}
        onChange={(event) => {
            const text = event.target.value;
            if (!isNumberText(text)) return;
            setDraft(text);
            const live = liveValue(text, minimum, maximum);
            if (live !== null && live !== value) onCommit(live);
        }}
        onBlur={finish}
        onKeyDown={(event) => { if (event.key === 'Enter') { event.preventDefault(); finish(); event.currentTarget.blur(); } }}
    /></label>;
}
