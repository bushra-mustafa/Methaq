import type { LayerFrame } from './Layer';
import type { AssetType, EventCategory } from './Library';

export interface EditorTemplate {
    id: number;
    name: string;
    category: EventCategory;
    categoryLabel: string;
    isActive: boolean;
    priceMinor: number;
}

export interface EditorAsset {
    id: string;
    slug: string;
    name: string;
    type: AssetType;
    previewUrl: string;
    fontFamily: string | null;
    fontUrl: string | null;
    version: number;
    width: number | null;
    height: number | null;
    capabilities: Record<string, unknown>;
}

export interface EditorCollectionItem {
    assetId: string;
    placement: LayerFrame & { locked: boolean };
    sortOrder: number;
}

export interface EditorCollection {
    id: string;
    slug: string;
    name: string;
    thumbnailUrl: string | null;
    items: EditorCollectionItem[];
}

export type EditorPanel = 'elements' | 'text' | 'layers' | 'colors' | 'material' | 'phrases';

export interface CanvasGuides {
    horizontal: number | null;
    vertical: number | null;
}
