import type { LayerFrame } from './Layer';
import type { AssetType } from './Library';

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

export type EditorPanel = 'elements' | 'text' | 'layers' | 'colors';

export interface CanvasGuides {
    horizontal: number | null;
    vertical: number | null;
}
