/**
 * Rules for typing into a number box. The value must not be forced into range on every keystroke:
 * typing "35" into a size box that needs at least 8 passes through "3", and an emptied box must not become 0.
 */

/** The number to apply while the person is still typing, or null when the text is not a finished value yet. */
export function liveValue(text: string, minimum: number, maximum: number): number | null {
    const trimmed = text.trim();
    if (trimmed === '' || trimmed === '-' || trimmed === '.' || trimmed.endsWith('.')) return null;
    const parsed = Number(trimmed);
    if (!Number.isFinite(parsed) || parsed < minimum || parsed > maximum) return null;
    return parsed;
}

/** The number to keep when the person leaves the box: clamped into range, or the old value if the box is empty or invalid. */
export function finalValue(text: string, minimum: number, maximum: number, fallback: number): number {
    const trimmed = text.trim();
    const parsed = trimmed === '' ? Number.NaN : Number(trimmed);
    return Number.isFinite(parsed) ? Math.min(maximum, Math.max(minimum, parsed)) : fallback;
}

/** Only digits, one minus sign at the start and one decimal point may be typed. */
export function isNumberText(text: string): boolean {
    return /^-?\d*\.?\d*$/.test(text);
}
