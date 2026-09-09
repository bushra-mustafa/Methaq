import { Head } from '@inertiajs/react';
import { AdminLibraryStatusForm } from '../../../../Domains/Editor/Components/AdminLibraryStatusForm';
import { AppShell } from '../../../../Domains/Users/Components/AppShell';
import type { LibraryAsset, LibraryCollection, LibraryTemplate } from '../../../../Types/Library';
import '../../../../../css/admin-library.css';

interface AdminLibraryProps {
    templates: LibraryTemplate[];
    assets: LibraryAsset[];
    collections: LibraryCollection[];
}

interface LibraryRowProps {
    resource: 'template' | 'asset' | 'collection';
    id: string;
    name: string;
    detail: string;
    previewUrl: string | null;
    isActive: boolean;
}

function LibraryRow({ resource, id, name, detail, previewUrl, isActive }: LibraryRowProps) {
    return <li className="admin-library-row">
        {previewUrl ? <img src={previewUrl} alt="" /> : <span className="admin-library-placeholder" aria-hidden="true">م</span>}
        <div className="admin-library-copy"><div><strong>{name}</strong><span className={isActive ? 'is-active' : 'is-inactive'}>{isActive ? 'نشط' : 'متوقف'}</span></div><p>{detail}</p></div>
        <AdminLibraryStatusForm resource={resource} id={id} isActive={isActive} />
    </li>;
}

export default function AdminLibrary({ templates, assets, collections }: AdminLibraryProps) {
    return <AppShell eyebrow="إدارة المحتوى" title="مكتبة القوالب والأصول">
        <Head title="إدارة مكتبة القوالب" />
        <p className="admin-library-intro">تعطيل العنصر يخفيه من مكتبة العملاء دون حذفه من التصاميم القديمة. كل تغيير يحتاج سبباً ويُسجل في سجل التدقيق.</p>
        <section className="admin-library-section"><header><h2>القوالب</h2><span>{templates.length}</span></header><ul>{templates.map((template) => <LibraryRow key={template.id} resource="template" id={template.id} name={template.name} detail={`${template.category} · ${template.assets.length} عناصر مقترحة`} previewUrl={template.thumbnailUrl} isActive={template.isActive} />)}</ul></section>
        <section className="admin-library-section"><header><h2>مجموعات العناصر</h2><span>{collections.length}</span></header><ul>{collections.map((collection) => <LibraryRow key={collection.id} resource="collection" id={collection.id} name={collection.name} detail={`${collection.items.length} عناصر`} previewUrl={collection.thumbnailUrl} isActive={collection.isActive} />)}</ul></section>
        <section className="admin-library-section"><header><h2>الأصول</h2><span>{assets.length}</span></header><ul>{assets.map((asset) => <LibraryRow key={asset.id} resource="asset" id={asset.id} name={asset.name} detail={`${asset.type} · الإصدار ${asset.version}`} previewUrl={asset.previewUrl} isActive={asset.isActive} />)}</ul></section>
    </AppShell>;
}
