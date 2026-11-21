import { pngFileFromDataUrl } from './pngFileFromDataUrl.ts';

function csrfToken(): string {
    const cookie = document.cookie.split('; ').find((item) => item.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : '';
}

/**
 * Sends the watermarked 540px preview captured in the studio. Rejects when the server refuses it.
 * The image is decoded in the page: outside local development, `connect-src 'self'` blocks fetching a data URL.
 */
export async function uploadTemplateThumbnail(templateId: string, dataUrl: string): Promise<void> {
    const form = new FormData();
    form.append('preview', pngFileFromDataUrl(dataUrl), 'preview.png');
    const response = await fetch(`/app/studio/templates/${templateId}/thumbnail`, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-XSRF-TOKEN': csrfToken() },
        credentials: 'same-origin',
        body: form,
    });
    if (!response.ok) throw new Error(`The preview was refused (${response.status}).`);
}
