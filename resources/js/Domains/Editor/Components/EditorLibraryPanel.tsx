import { useMemo, useState } from 'react';
import type { EditorAsset, EditorCollection } from '../../../Types/Editor';
import type { AssetType } from '../../../Types/Library';

interface EditorLibraryPanelProps {
    assets: EditorAsset[];
    collections: EditorCollection[];
    recommendedAssetIds: string[];
    placedAssetIds: string[];
    onAddAsset: (asset: EditorAsset) => void;
    onAddCollection: (collection: EditorCollection) => void;
}

const filters: Array<{ value: 'all' | AssetType; label: string }> = [
    { value: 'all', label: 'الكل' },
    { value: 'frame', label: 'إطارات' },
    { value: 'decoration', label: 'زخارف' },
    { value: 'icon', label: 'أيقونات' },
    { value: 'background', label: 'خلفيات' },
];

export function EditorLibraryPanel({ assets, collections, recommendedAssetIds, placedAssetIds, onAddAsset, onAddCollection }: EditorLibraryPanelProps) {
    const [query, setQuery] = useState('');
    const [filter, setFilter] = useState<'all' | AssetType>('all');
    const recommended = useMemo(() => new Set(recommendedAssetIds), [recommendedAssetIds]);
    const placed = useMemo(() => new Set(placedAssetIds), [placedAssetIds]);
    const visibleAssets = useMemo(() => assets.filter((asset) => {
        if (asset.type === 'font' || asset.type === 'audio') return false;
        return (filter === 'all' || asset.type === filter) && asset.name.toLocaleLowerCase('ar').includes(query.trim().toLocaleLowerCase('ar'));
    }), [assets, filter, query]);

    return <section className="editor-panel-section" aria-labelledby="elements-title">
        <header><span>01</span><div><h2 id="elements-title">العناصر</h2><p>اجمعي الإطار والزخارف بالطريقة التي تناسبك.</p></div></header>
        <label className="editor-search"><span>بحث في العناصر</span><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="مثلاً: إطار بودري" /></label>
        <div className="editor-filter-row" role="group" aria-label="تصفية العناصر">
            {filters.map((item) => <button className={filter === item.value ? 'is-active' : ''} type="button" key={item.value} onClick={() => setFilter(item.value)}>{item.label}</button>)}
        </div>
        {collections.length > 0 && <div className="editor-collections"><h3>مجموعات جاهزة</h3>{collections.map((collection) => {
            const isApplied = collection.items.every((item) => placed.has(item.assetId));
            return <button type="button" key={collection.id} disabled={isApplied} onClick={() => onAddCollection(collection)}><span>{collection.thumbnailUrl ? <img src={collection.thumbnailUrl} alt="" /> : 'م'}</span><strong>{collection.name}</strong><small>{isApplied ? 'مضافة' : 'إضافة المجموعة'}</small></button>;
        })}</div>}
        <div className="editor-assets-grid">
            {visibleAssets.map((asset) => <button type="button" key={asset.id} onClick={() => onAddAsset(asset)}>
                <span className="editor-asset-preview"><img src={asset.previewUrl} alt="" loading="lazy" />{recommended.has(asset.id) && <small>مقترح</small>}</span>
                <strong>{asset.name}</strong><span>إضافة</span>
            </button>)}
        </div>
        {visibleAssets.length === 0 && <p className="editor-empty">لا توجد عناصر مطابقة لهذا البحث.</p>}
    </section>;
}
