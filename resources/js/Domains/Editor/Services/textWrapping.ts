/**
 * Canvas text wraps at spaces only. Breaking at any grapheme cut Arabic words in the middle
 * («أنف / سكم»); a single word longer than the box is allowed to overflow instead.
 */
export const TEXT_WRAP_OPTIONS = { splitByGrapheme: false } as const;
