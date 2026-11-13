export type EventCategory = 'wedding' | 'henna' | 'marriage_contract' | 'graduation' | 'project_opening' | 'newborn';

/** Arabic names of the categories, shared by every card that shows one. */
export const categoryLabels: Record<EventCategory, string> = {
    wedding: 'زفاف',
    henna: 'حنّة',
    marriage_contract: 'عقد قران',
    graduation: 'تخرج',
    project_opening: 'افتتاح مشروع',
    newborn: 'حفل مولود',
};
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
    thumbnailWebpUrl: string | null;
    thumbnailAvifUrl: string | null;
    palette: Record<string, string>;
    isActive: boolean;
    /** Chosen by the admin for the home page's featured section. */
    isFeatured: boolean;
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
    thumbnailWebpUrl: string | null;
    thumbnailAvifUrl: string | null;
    isActive: boolean;
    items: LibraryCollectionItem[];
}

export interface CategoryFilter {
    value: EventCategory;
    label: string;
}
