import type { Canvas, FabricObject } from 'fabric';
import type { PresentationCard } from '../../../Types/PresentationCard';

export function capturePresentationCard(canvas: Canvas, isText: (object: FabricObject) => boolean): PresentationCard {
    const groups: { objects: FabricObject[]; text: boolean }[] = [];
    for (const object of canvas.getObjects().filter((item) => item.visible && !item.excludeFromExport)) {
        const text = isText(object);
        const previous = groups.at(-1);
        if (!text && previous && !previous.text) previous.objects.push(object);
        else groups.push({ objects: [object], text });
    }
    const background = canvas.backgroundColor;
    const width = canvas.width;
    const height = canvas.height;
    try {
        canvas.backgroundColor = '';
        return { width, height, background: typeof background === 'string' ? background : 'transparent', parts: groups.flatMap((group) => {
            const bounds = group.objects.map((object) => object.getBoundingRect());
            const left = Math.max(0, Math.floor(Math.min(...bounds.map((rect) => rect.left))) - 2);
            const top = Math.max(0, Math.floor(Math.min(...bounds.map((rect) => rect.top))) - 2);
            const right = Math.min(width, Math.ceil(Math.max(...bounds.map((rect) => rect.left + rect.width))) + 2);
            const bottom = Math.min(height, Math.ceil(Math.max(...bounds.map((rect) => rect.top + rect.height))) + 2);
            if (right <= left || bottom <= top) return [];
            return [{ url: canvas.toDataURL({ format: 'png', multiplier: 1, enableRetinaScaling: false, left, top, width: right - left, height: bottom - top, filter: (object) => group.objects.some((member) => member === object) }), x: left, y: top, width: right - left, height: bottom - top, text: group.text }];
        }) };
    } finally {
        canvas.backgroundColor = background;
    }
}
