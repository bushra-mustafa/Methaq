import type { DesignDocument } from '../../../Types/DesignDocument';
import type { Layer, TextAlignment } from '../../../Types/Layer';
import type { ColorValue, HexColor, Palette } from '../../../Types/Palette';

interface FabricObjectBase {
    id: string;
    left: number;
    top: number;
    width: number;
    height: number;
    scaleX: number;
    scaleY: number;
    angle: number;
    opacity: number;
    visible: boolean;
    selectable: boolean;
    lockMovementX: boolean;
    lockMovementY: boolean;
    lockRotation: boolean;
    lockScalingX: boolean;
    lockScalingY: boolean;
    methaqLayer: Layer;
}

export interface FabricTextDescriptor extends FabricObjectBase {
    fabricType: 'textbox';
    text: string;
    fontAssetId: string;
    fontAssetVersion: number;
    fontSize: number;
    fontWeight: number;
    textAlign: TextAlignment;
    direction: 'rtl' | 'ltr';
    fill: HexColor;
}

export interface FabricImageDescriptor extends FabricObjectBase {
    fabricType: 'image';
    assetId: string;
    assetVersion: number;
    fit: 'contain' | 'cover';
}

export interface FabricShapeDescriptor extends FabricObjectBase {
    fabricType: 'rect' | 'ellipse' | 'line';
    fill: HexColor | null;
    stroke: HexColor | null;
    strokeWidth: number;
}

export type FabricObjectDescriptor = FabricTextDescriptor | FabricImageDescriptor | FabricShapeDescriptor;

export interface FabricCanvasDescriptor {
    width: number;
    height: number;
    backgroundColor: HexColor;
    objects: FabricObjectDescriptor[];
}

function resolveColor(color: ColorValue, palette: Palette): HexColor {
    return color.source === 'palette' ? palette.values[color.role] : color.value;
}

function baseDescriptor(layer: Layer): FabricObjectBase {
    return {
        id: layer.id,
        left: layer.frame.x,
        top: layer.frame.y,
        width: layer.frame.width,
        height: layer.frame.height,
        scaleX: layer.frame.scaleX,
        scaleY: layer.frame.scaleY,
        angle: layer.frame.rotation,
        opacity: layer.frame.opacity,
        visible: layer.visible,
        selectable: !layer.locked,
        lockMovementX: layer.locked,
        lockMovementY: layer.locked,
        lockRotation: layer.locked,
        lockScalingX: layer.locked,
        lockScalingY: layer.locked,
        methaqLayer: layer,
    };
}

export function toFabricObject(layer: Layer, palette: Palette): FabricObjectDescriptor {
    const base = baseDescriptor(layer);
    if (layer.type === 'text') {
        return {
            ...base,
            fabricType: 'textbox',
            text: layer.content,
            fontAssetId: layer.font.assetId,
            fontAssetVersion: layer.font.version,
            fontSize: layer.fontSize,
            fontWeight: layer.fontWeight,
            textAlign: layer.alignment,
            direction: layer.direction === 'ltr' ? 'ltr' : 'rtl',
            fill: resolveColor(layer.fill, palette),
        };
    }

    if (layer.type === 'image') {
        return {
            ...base,
            fabricType: 'image',
            assetId: layer.asset.assetId,
            assetVersion: layer.asset.version,
            fit: layer.fit,
        };
    }

    return {
        ...base,
        fabricType: layer.shapeKind === 'rectangle' ? 'rect' : layer.shapeKind,
        fill: layer.fill === null ? null : resolveColor(layer.fill, palette),
        stroke: layer.stroke === null ? null : resolveColor(layer.stroke, palette),
        strokeWidth: layer.strokeWidth,
    };
}

export function fromFabricObject(descriptor: FabricObjectDescriptor): Layer {
    const source = descriptor.methaqLayer;
    const common = {
        ...source,
        frame: {
            x: descriptor.left,
            y: descriptor.top,
            width: descriptor.width,
            height: descriptor.height,
            scaleX: descriptor.scaleX,
            scaleY: descriptor.scaleY,
            rotation: descriptor.angle,
            opacity: descriptor.opacity,
        },
        visible: descriptor.visible,
        locked: !descriptor.selectable,
    };

    if (descriptor.fabricType === 'textbox' && source.type === 'text') {
        return {
            ...common,
            type: 'text',
            content: descriptor.text,
            font: { assetId: descriptor.fontAssetId, version: descriptor.fontAssetVersion },
            fontSize: descriptor.fontSize,
            fontWeight: descriptor.fontWeight,
            alignment: descriptor.textAlign,
            language: source.language,
            direction: source.direction,
            fill: source.fill,
        };
    }

    if (descriptor.fabricType === 'image' && source.type === 'image') {
        return {
            ...common,
            type: 'image',
            asset: { assetId: descriptor.assetId, version: descriptor.assetVersion },
            fit: descriptor.fit,
        };
    }

    if (descriptor.fabricType !== 'textbox' && descriptor.fabricType !== 'image' && source.type === 'shape') {
        return {
            ...common,
            type: 'shape',
            shapeKind: descriptor.fabricType === 'rect' ? 'rectangle' : descriptor.fabricType,
            fill: source.fill,
            stroke: source.stroke,
            strokeWidth: descriptor.strokeWidth,
        };
    }

    throw new Error(`نوع Fabric لا يطابق طبقة ميثاق ${source.id}.`);
}

export function toFabricDocument(document: DesignDocument): FabricCanvasDescriptor {
    return {
        width: document.canvas.width,
        height: document.canvas.height,
        backgroundColor: resolveColor(document.canvas.background, document.palette),
        objects: document.canvas.layers.map((layer) => toFabricObject(layer, document.palette)),
    };
}

export function fromFabricDocument(descriptor: FabricCanvasDescriptor, source: DesignDocument): DesignDocument {
    return {
        ...source,
        canvas: {
            ...source.canvas,
            layers: descriptor.objects.map(fromFabricObject),
        },
    };
}
