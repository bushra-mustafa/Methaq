export type EventCategory = 'wedding' | 'henna' | 'marriage_contract' | 'graduation';
export type AssetType = 'background' | 'frame' | 'icon' | 'font' | 'decoration' | 'audio';
export type LibraryResource = 'template' | 'asset' | 'collection';

export interface LibraryAsset {
    id: string;
    slug: string;
    name: string;
    type: AssetType;
    previewUrl: string;
    version: number;
    isActive: boolean;
    capabilities: Record<string, unknown>;
}

export interface LibraryTemplate {
    id: string;
    slug: string;
    name: string;
    category: EventCategory;
    thumbnailUrl: string;
    palette: Record<string, string>;
    isActive: boolean;
    assets: LibraryAsset[];
}

export interface LibraryCollectionItem {
    asset: LibraryAsset;
    placement: Record<string, unknown>;
    sortOrder: number;
}

export interface LibraryCollection {
    id: string;
    slug: string;
    name: string;
    thumbnailUrl: string | null;
    isActive: boolean;
    items: LibraryCollectionItem[];
}

export interface CategoryFilter {
    value: EventCategory;
    label: string;
}
