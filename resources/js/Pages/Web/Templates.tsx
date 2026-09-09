import { Head, Link } from '@inertiajs/react';
import { LibraryCollectionCard } from '../../Domains/Editor/Components/LibraryCollectionCard';
import { LibraryTemplateCard } from '../../Domains/Editor/Components/LibraryTemplateCard';
import type { CategoryFilter, EventCategory, LibraryCollection, LibraryTemplate } from '../../Types/Library';
import '../../../css/library.css';

interface TemplatesProps {
    templates: LibraryTemplate[];
    collections: LibraryCollection[];
    categories: CategoryFilter[];
    selectedCategory: EventCategory | null;
}

export default function Templates({ templates, collections, categories, selectedCategory }: TemplatesProps) {
    return <main className="library-page">
        <Head title="مكتبة القوالب" />
        <header className="library-nav"><Link href="/"><img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="ميثاق — Methaq" /></Link><nav><Link href="/login">دخول</Link><Link className="library-nav-primary" href="/register">ابدئي دعوتك</Link></nav></header>
        <section className="library-hero"><span>Mix &amp; Match · اختاري وركّبي</span><h1>اتجاهٌ تبدأ منه الدعوة،<br />والتفاصيل لكِ.</h1><p>اختاري طابعاً أولياً، ثم بدّلي الإطار والخط واللون والعناصر بحرية داخل المحرر.</p></section>
        <nav className="library-filters" aria-label="تصنيف القوالب"><Link className={selectedCategory === null ? 'is-selected' : ''} href="/templates">الكل</Link>{categories.map((category) => <Link key={category.value} className={selectedCategory === category.value ? 'is-selected' : ''} href={`/templates?category=${category.value}`}>{category.label}</Link>)}</nav>
        <section className="library-section" aria-labelledby="templates-title"><div className="library-section-heading"><span>البدايات المقترحة</span><h2 id="templates-title">القوالب</h2><p>{templates.length} اتجاهات متاحة في هذا التصنيف</p></div>{templates.length === 0 ? <p className="library-empty">لا توجد قوالب نشطة في هذا التصنيف حالياً.</p> : <div className="library-template-grid">{templates.map((template) => <LibraryTemplateCard key={template.id} template={template} />)}</div>}</section>
        <section className="library-section library-collections" aria-labelledby="collections-title"><div className="library-section-heading"><span>تُستخدم مع أي قالب</span><h2 id="collections-title">مجموعات العناصر</h2><p>يمكن إضافة المجموعة كاملة، ثم تعديل كل عنصر بمفرده.</p></div><div className="library-collection-grid">{collections.map((collection) => <LibraryCollectionCard key={collection.id} collection={collection} />)}</div></section>
        <footer className="library-footer"><img src="/brand/logos/methaq-symbol-small-flat.svg" alt="" /><p>هذه معاينات منخفضة التفاصيل. الأصول الأصلية تبقى داخل التخزين الخاص وتفتح للمحرر حسب الصلاحية.</p></footer>
    </main>;
}
