/** Decodes locally because the content security policy blocks fetching data URLs. */
export function pngFileFromDataUrl(dataUrl: string): File {
    const [header = '', body = ''] = dataUrl.split(',');
    if (!header.startsWith('data:image/png') || !header.endsWith(';base64')) throw new Error('Unexpected card image');
    const binary = atob(body);
    const bytes = new Uint8Array(binary.length);
    for (let index = 0; index < binary.length; index++) bytes[index] = binary.charCodeAt(index);
    return new File([bytes], 'card.png', { type: 'image/png' });
}
