export const TEXT_SIZE_LIMITS = { minimumFontSize: 8, maximumFontSize: 512, minimumWidth: 20 } as const;

interface TextScaleInput { width: number; fontSize: number; scaleX: number; scaleY: number }

/**
 * Dragging a corner handle on a text box should make the text bigger or smaller (a new font size);
 * dragging a side handle should make the box wider or narrower (a new width). Either way the stored
 * scale goes back to 1, so the size shown in the panel is the real size and the text is never stretched.
 */
export function normalizeTextScale(input: TextScaleInput, maximumWidth: number): { width: number; fontSize: number } {
    const uniform = Math.abs(input.scaleX - input.scaleY) < 0.02;
    const fontSize = uniform
        ? Math.min(TEXT_SIZE_LIMITS.maximumFontSize, Math.max(TEXT_SIZE_LIMITS.minimumFontSize, Math.round(input.fontSize * input.scaleY)))
        : input.fontSize;
    const width = Math.min(maximumWidth, Math.max(TEXT_SIZE_LIMITS.minimumWidth, Math.round(input.width * input.scaleX)));

    return { width, fontSize };
}
