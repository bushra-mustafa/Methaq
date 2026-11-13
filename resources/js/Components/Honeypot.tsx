import type { ChangeEvent } from "react";

interface HoneypotProps {
    value: string;
    onChange: (event: ChangeEvent<HTMLInputElement>) => void;
}

/** A field people never see or reach; a bot that fills every input gives itself away. */
export default function Honeypot({ value, onChange }: HoneypotProps) {
    return (
        <div aria-hidden="true" className="absolute -start-[9999px] size-px overflow-hidden">
            <label>
                الموقع الإلكتروني
                <input type="text" name="website" value={value} onChange={onChange} tabIndex={-1} autoComplete="off" />
            </label>
        </div>
    );
}
