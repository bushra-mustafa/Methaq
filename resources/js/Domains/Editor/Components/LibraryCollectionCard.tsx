import type { LibraryCollection } from '../../../Types/Library';

interface LibraryCollectionCardProps {
    collection: LibraryCollection;
}

export function LibraryCollectionCard({ collection }: LibraryCollectionCardProps) {
    return <article className="library-collection-card">
        {collection.thumbnailUrl && <img src={collection.thumbnailUrl} alt={`معاينة ${collection.name}`} loading="lazy" />}
        <div><h3>{collection.name}</h3><p>{collection.items.map((item) => item.asset.name).join(' · ')}</p><span>{collection.items.length} عناصر تضاف معاً أو منفردة</span></div>
    </article>;
}
