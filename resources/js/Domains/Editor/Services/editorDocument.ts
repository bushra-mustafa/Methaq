import type { DesignDocument } from '../../../Types/DesignDocument';
import type { EditorAsset, EditorCollection } from '../../../Types/Editor';
import type { ImageLayer, Layer, LayerFrame, TextLayer } from '../../../Types/Layer';
import type { ColorValue, HexColor, PaletteRole } from '../../../Types/Palette';

let generatedLayerSequence = 0;

function layerId(prefix: string): string {
    generatedLayerSequence += 1;
    return `${prefix}_${Date.now().toString(36)}_${generatedLayerSequence.toString(36)}`;
}

function frameForAsset(asset: EditorAsset): LayerFrame {
    if (asset.type === 'frame' || asset.type === 'background') {
        return { x: 0, y: 0, width: 1080, height: 1920, scaleX: 1, scaleY: 1, rotation: 0, opacity: 1 };
    }

    const sourceWidth = asset.width ?? 360;
    const sourceHeight = asset.height ?? 360;
    const width = asset.type === 'icon' ? 240 : Math.min(sourceWidth, 480);
    const height = Math.round(width * (sourceHeight / sourceWidth));

    return { x: Math.round((1080 - width) / 2), y: Math.round((1920 - height) / 2), width, height, scaleX: 1, scaleY: 1, rotation: 0, opacity: 1 };
}

function imageLayer(asset: EditorAsset, frame = frameForAsset(asset), locked = false): ImageLayer {
    return {
        id: layerId(asset.type === 'frame' ? 'frame' : 'asset'),
        type: 'image',
        frame,
        locked,
        visible: true,
        asset: { assetId: asset.id, version: asset.version },
        fit: 'contain',
    };
}

export function addAsset(document: DesignDocument, asset: EditorAsset): DesignDocument {
    if (asset.type === 'font' || asset.type === 'audio') return document;

    return {
        ...document,
        canvas: { ...document.canvas, layers: [...document.canvas.layers, imageLayer(asset)] },
    };
}

export function addCollection(document: DesignDocument, collection: EditorCollection, assets: Map<string, EditorAsset>): DesignDocument {
    const placedAssetIds = new Set(document.canvas.layers
        .filter((layer): layer is ImageLayer => layer.type === 'image')
        .map((layer) => layer.asset.assetId));
    const additions = collection.items
        .filter((item) => !placedAssetIds.has(item.assetId))
        .map((item) => {
            const asset = assets.get(item.assetId);
            return asset && asset.type !== 'font' && asset.type !== 'audio'
                ? imageLayer(asset, {
                    x: item.placement.x,
                    y: item.placement.y,
                    width: item.placement.width,
                    height: item.placement.height,
                    scaleX: item.placement.scaleX,
                    scaleY: item.placement.scaleY,
                    rotation: item.placement.rotation,
                    opacity: item.placement.opacity,
                }, item.placement.locked)
                : null;
        })
        .filter((layer): layer is ImageLayer => layer !== null);

    if (additions.length === 0) return document;

    return { ...document, canvas: { ...document.canvas, layers: [...document.canvas.layers, ...additions] } };
}

export function addText(document: DesignDocument, font: EditorAsset): DesignDocument {
    const layer: TextLayer = {
        id: layerId('text'),
        type: 'text',
        frame: { x: 140, y: 780, width: 800, height: 180, scaleX: 1, scaleY: 1, rotation: 0, opacity: 1 },
        locked: false,
        visible: true,
        content: 'اكتبي النص هنا',
        language: 'ar',
        direction: 'rtl',
        alignment: 'center',
        font: { assetId: font.id, version: font.version },
        fontSize: 64,
        fontWeight: 400,
        fill: { source: 'palette', role: 'primaryText' },
    };

    return { ...document, canvas: { ...document.canvas, layers: [...document.canvas.layers, layer] } };
}

export function updateLayer(document: DesignDocument, id: string, update: (layer: Layer) => Layer): DesignDocument {
    let changed = false;
    const layers = document.canvas.layers.map((layer) => {
        if (layer.id !== id) return layer;
        const next = update(layer);
        changed = next !== layer;
        return next;
    });

    return changed ? { ...document, canvas: { ...document.canvas, layers } } : document;
}

export function removeLayer(document: DesignDocument, id: string): DesignDocument {
    const layers = document.canvas.layers.filter((layer) => layer.id !== id);
    return layers.length === document.canvas.layers.length ? document : { ...document, canvas: { ...document.canvas, layers } };
}

export function moveLayer(document: DesignDocument, id: string, direction: 'forward' | 'backward'): DesignDocument {
    const index = document.canvas.layers.findIndex((layer) => layer.id === id);
    const target = direction === 'forward' ? index + 1 : index - 1;
    if (index < 0 || target < 0 || target >= document.canvas.layers.length) return document;

    const layers = [...document.canvas.layers];
    const currentLayer = layers[index];
    const targetLayer = layers[target];
    if (!currentLayer || !targetLayer) return document;
    layers[index] = targetLayer;
    layers[target] = currentLayer;

    return { ...document, canvas: { ...document.canvas, layers } };
}

export function replaceImageAsset(document: DesignDocument, id: string, asset: EditorAsset): DesignDocument {
    return updateLayer(document, id, (layer) => layer.type === 'image' ? {
        ...layer,
        asset: { assetId: asset.id, version: asset.version },
    } : layer);
}

export function updatePaletteColor(document: DesignDocument, role: PaletteRole, color: HexColor): DesignDocument {
    return {
        ...document,
        palette: { ...document.palette, values: { ...document.palette.values, [role]: color } },
    };
}

export function resolveColor(color: ColorValue, document: Pick<DesignDocument, 'palette'>): HexColor {
    return color.source === 'palette' ? document.palette.values[color.role] : color.value;
}

export function layerWarnings(document: DesignDocument, layer: Layer | null): string[] {
    if (!layer) return [];
    const width = layer.frame.width * layer.frame.scaleX;
    const height = layer.frame.height * layer.frame.scaleY;
    const warnings: string[] = [];
    if (layer.frame.x < 0 || layer.frame.y < 0 || layer.frame.x + width > document.canvas.width || layer.frame.y + height > document.canvas.height) {
        warnings.push('جزء من العنصر خارج حدود البطاقة وقد يتعرض للقص.');
    }

    if (layer.type === 'text') {
        const estimatedLines = Math.max(1, layer.content.split('\n').length, Math.ceil(Array.from(layer.content).length * layer.fontSize * 0.55 / layer.frame.width));
        if (estimatedLines * layer.fontSize * 1.35 > layer.frame.height * layer.frame.scaleY) {
            warnings.push('النص قد يتجاوز المساحة المحددة؛ كبّري الصندوق أو صغّري الخط.');
        }
        if (contrastRatio(resolveColor(layer.fill, document), resolveColor(document.canvas.background, document)) < 3) {
            warnings.push('التباين بين النص والخلفية منخفض وقد تصعب قراءته.');
        }
    }

    return warnings;
}

function contrastRatio(first: HexColor, second: HexColor): number {
    const luminance = (hex: HexColor): number => {
        const values = [hex.slice(1, 3), hex.slice(3, 5), hex.slice(5, 7)].map((channel) => Number.parseInt(channel, 16) / 255);
        const [red = 0, green = 0, blue = 0] = values.map((value) => value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4);
        return 0.2126 * red + 0.7152 * green + 0.0722 * blue;
    };
    const lighter = Math.max(luminance(first), luminance(second));
    const darker = Math.min(luminance(first), luminance(second));
    return (lighter + 0.05) / (darker + 0.05);
}
