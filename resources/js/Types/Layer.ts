import type { ColorValue } from './Palette';

export type Language = 'ar' | 'en' | 'mixed';
export type TextDirection = 'rtl' | 'ltr' | 'auto';
export type TextAlignment = 'left' | 'center' | 'right';
export type ShapeKind = 'rectangle' | 'ellipse' | 'line';
export type ImageFit = 'contain' | 'cover';

export interface AssetReference {
    assetId: string;
    version: number;
}

export interface LayerFrame {
    x: number;
    y: number;
    width: number;
    height: number;
    scaleX: number;
    scaleY: number;
    rotation: number;
    opacity: number;
}

interface BaseLayer {
    id: string;
    frame: LayerFrame;
    locked: boolean;
    visible: boolean;
}

export interface TextLayer extends BaseLayer {
    type: 'text';
    content: string;
    language: Language;
    direction: TextDirection;
    alignment: TextAlignment;
    font: AssetReference;
    fontSize: number;
    fontWeight: number;
    fill: ColorValue;
}

export interface ImageLayer extends BaseLayer {
    type: 'image';
    asset: AssetReference;
    fit: ImageFit;
}

export interface ShapeLayer extends BaseLayer {
    type: 'shape';
    shapeKind: ShapeKind;
    fill: ColorValue | null;
    stroke: ColorValue | null;
    strokeWidth: number;
}

export type Layer = TextLayer | ImageLayer | ShapeLayer;
