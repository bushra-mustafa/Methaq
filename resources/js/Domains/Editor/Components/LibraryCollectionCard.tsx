import Thumbnail from '../../../Components/Thumbnail';
import type { LibraryCollection } from '../../../Types/Library';

interface LibraryCollectionCardProps {
    collection: LibraryCollection;
}

export function LibraryCollectionCard({ collection }: LibraryCollectionCardProps) {
    return <article className="library-collection-card">
        {collection.thumbnailUrl && <Thumbnail src={collection.thumbnailUrl} avif={collection.thumbnailAvifUrl} webp={collection.thumbnailWebpUrl} alt={`معاينة مجموعة ${collection.name}`} width={540} height={360} />}
        <div><h3>{collection.name}</h3><p>{collection.items.map((item) => item.asset.name).join(' · ')}</p><span>{collection.items.length} عناصر تضاف معاً أو منفردة</span></div>
    </article>;
}
