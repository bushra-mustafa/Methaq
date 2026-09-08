// Lucide Flower2 and Leaf paths; license: resources/brand/licenses/lucide.txt.
const flower = '<path d="M12 5a3 3 0 1 1 3 3m-3-3a3 3 0 1 0-3 3m3-3v1M9 8a3 3 0 1 0 3 3M9 8h1m5 0a3 3 0 1 1-3 3m3-3h-1m-2 3v-1"/><circle cx="12" cy="8" r="2"/><path d="M12 10v12M12 22c4.2 0 7-1.667 7-5-4.2 0-7 1.667-7 5ZM12 22c-4.2 0-7-1.667-7-5 4.2 0 7 1.667 7 5Z"/>';
const leaf = '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10ZM2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>';
function hex(value: string): string {
    if (!/^#[0-9a-f]{6}$/i.test(value)) throw new Error('Invalid artwork color');
    return value;
}
export function floralMarkup(rose: string, foliage: string): string {
    return `<g fill="none" stroke-linecap="round" stroke-linejoin="round">
      <g stroke="${hex(foliage)}" stroke-width="0.65"><g transform="translate(0 52) rotate(-32 54 71) scale(4.4 5.8)">${leaf}</g><g transform="translate(51 100) rotate(36 54 71) scale(4.4 5.8)">${leaf}</g></g>
      <g stroke="${hex(rose)}" stroke-width="0.6"><g transform="translate(31 77) scale(3.45)">${flower}</g><g transform="translate(55 38) scale(1.66)">${flower}</g><g transform="translate(42 183) scale(1.2)">${flower}</g></g>
    </g>`;
}
export function floralImage(rose: string, foliage: string): string {
    return `data:image/svg+xml;charset=utf-8,${encodeURIComponent(`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 180 260">${floralMarkup(rose, foliage)}</svg>`)}`;
}
export interface ArtworkColors { background: string; paper: string; accent: string; envelope: string; florals: boolean }
export function cardArtwork(colors: ArtworkColors): string {
    const { background, paper, accent, envelope, florals } = colors;
    [background, paper, accent, envelope].forEach(hex);
    return `<svg xmlns="http://www.w3.org/2000/svg" width="1080" height="1920" viewBox="0 0 1080 1920">
      <rect width="1080" height="1920" fill="${background}"/>
      <path d="M100 1810V555C100 10 980 10 980 555V1810Z" fill="${paper}" stroke="${accent}" stroke-width="2"/>
      <path d="M121 1789V555C121 38 959 38 959 555V1789Z" fill="none" stroke="${accent}" stroke-opacity="0.45" stroke-width="2"/>
      ${florals ? `<g opacity="0.85" transform="translate(842 230) rotate(15 100 100) scale(1.7)">${floralMarkup(envelope, accent)}</g><g opacity="0.85" transform="translate(188 1800) rotate(195) scale(1.7)">${floralMarkup(envelope, accent)}</g>` : ''}
      <g fill="none" stroke="${accent}" stroke-width="3"><circle cx="515" cy="1260" r="33"/><circle cx="558" cy="1260" r="33"/><path d="M540 1218C505 1200 523 1180 540 1195C557 1180 575 1200 540 1218Z"/><path d="M375 1650h120m90 0h120"/></g>
      <path d="M540 1664C505 1646 523 1626 540 1641C557 1626 575 1646 540 1664Z" fill="none" stroke="${accent}" stroke-width="2"/>
    </svg>`;
}
