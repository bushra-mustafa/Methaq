export const WATERMARK_TEXT = 'ميثاق · METHAQ';

const ANGLE = (-28 * Math.PI) / 180;

export interface WatermarkTile {
    x: number;
    y: number;
}

/**
 * Tile origins for a rotated watermark that covers a width x height card: the grid is laid out in the
 * rotated frame, so it is generated over the card's diagonal and rotated back when drawn.
 */
export function watermarkTiles(width: number, height: number, stepX: number, stepY: number): WatermarkTile[] {
    const span = Math.ceil(Math.hypot(width, height));
    const tiles: WatermarkTile[] = [];
    for (let row = 0, y = -span; y <= span; row += 1, y += stepY) {
        for (let x = -span - (row % 2 === 0 ? 0 : stepX / 2); x <= span; x += stepX) tiles.push({ x, y });
    }

    return tiles;
}

function loadImage(url: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error('The preview image could not be read.'));
        image.src = url;
    });
}

/** Burns the tiled brand watermark into a PNG data URL. Used for every downloadable preview. */
/** `targetWidth` shrinks the marked image (keeping its ratio), e.g. for the 540px library preview. */
export async function watermarkPreview(dataUrl: string, targetWidth?: number): Promise<string> {
    const image = await loadImage(dataUrl);
    const canvas = document.createElement('canvas');
    canvas.width = image.naturalWidth;
    canvas.height = image.naturalHeight;
    const context = canvas.getContext('2d');
    if (!context) throw new Error('Canvas is not available.');

    const unit = canvas.width / 1080;
    const fontSize = Math.round(58 * unit);
    await document.fonts.load(`700 ${fontSize}px "Reem Kufi"`).catch(() => []);
    context.drawImage(image, 0, 0);
    context.font = `700 ${fontSize}px "Reem Kufi", "Noto Sans Arabic", sans-serif`;
    context.textBaseline = 'middle';
    context.fillStyle = 'rgba(110, 110, 110, 0.26)';
    context.shadowColor = 'rgba(255, 255, 255, 0.35)';
    context.shadowBlur = 2 * unit;
    context.translate(canvas.width / 2, canvas.height / 2);
    context.rotate(ANGLE);
    const stepX = Math.round(430 * unit);
    const stepY = Math.round(128 * unit);
    for (const tile of watermarkTiles(canvas.width, canvas.height, stepX, stepY)) context.fillText(WATERMARK_TEXT, tile.x, tile.y);

    if (targetWidth === undefined || targetWidth >= canvas.width) return canvas.toDataURL('image/png');

    const small = document.createElement('canvas');
    small.width = targetWidth;
    small.height = Math.round((canvas.height * targetWidth) / canvas.width);
    const smallContext = small.getContext('2d');
    if (!smallContext) throw new Error('Canvas is not available.');
    smallContext.imageSmoothingQuality = 'high';
    smallContext.drawImage(canvas, 0, 0, small.width, small.height);

    return small.toDataURL('image/png');
}
