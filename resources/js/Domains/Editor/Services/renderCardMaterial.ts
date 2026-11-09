import { FabricImage } from 'fabric';
import type { CanvasData } from '../../../Types/CanvasData';
import { materialTextureUrl } from './cardMaterials';

export async function renderCardMaterial(canvas: CanvasData, signal: AbortSignal): Promise<FabricImage | undefined> {
    const material = canvas.material;
    if (!material || material.preset === 'plain' || material.intensity === 0) return undefined;
    const url = materialTextureUrl(material.preset);
    if (!url) return undefined;

    const texture = await FabricImage.fromURL(url, { signal });
    texture.set({
        left: 0,
        top: 0,
        scaleX: canvas.width / texture.width,
        scaleY: canvas.height / texture.height,
        opacity: material.intensity,
        selectable: false,
        evented: false,
        objectCaching: false,
    });
    return texture;
}
