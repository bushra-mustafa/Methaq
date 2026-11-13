/**
 * Canvas text wraps at spaces only. Breaking at any grapheme cut Arabic words in the middle
 * («أنف / سكم»); a single word longer than the box is allowed to overflow instead.
 */
export const TEXT_WRAP_OPTIONS = { splitByGrapheme: false } as const;

/** Quranic text stacks tall marks above and below the line, so it needs more room between lines than plain text. */
export function textLineHeight(fontSlug: string | undefined): number {
    return fontSlug === 'font-amiri-quran' ? 1.7 : 1.25;
}
