import { Head, Link } from '@inertiajs/react';
import { LibraryCollectionCard } from '../../Domains/Editor/Components/LibraryCollectionCard';
import { LibraryTemplateCard } from '../../Domains/Editor/Components/LibraryTemplateCard';
import PublicLayout from '../../Layouts/PublicLayout';
import PageIntro from '../../Components/Public/PageIntro';
import PaperSurface from '../../Components/Public/Home/PaperSurface';
import Reveal from '../../Components/Public/Reveal';
import type { CategoryFilter, EventCategory, LibraryCollection, LibraryTemplate } from '../../Types/Library';
import '../../../css/library.css';

interface TemplatesProps {
    templates: LibraryTemplate[];
    collections: LibraryCollection[];
    categories: CategoryFilter[];
    selectedCategory: EventCategory | null;
}

export default function Templates({ templates, collections, categories, selectedCategory }: TemplatesProps) {
    return <PublicLayout>
        <Head title="مكتبة القوالب" />
        <div className="library-page">
            <PageIntro title="اتجاهٌ تبدأ منه الدعوة، والتفاصيل لكِ" lead="اختاري طابعاً أولياً، ثم بدّلي الإطار والخط واللون والعناصر بحرية داخل المحرر." />
            <nav className="library-filters" aria-label="تصنيف القوالب"><Link className={selectedCategory === null ? 'is-selected' : ''} href="/templates">الكل</Link>{categories.map((category) => <Link key={category.value} className={selectedCategory === category.value ? 'is-selected' : ''} href={`/templates?category=${category.value}`}>{category.label}</Link>)}</nav>
            <section className="library-section" aria-labelledby="templates-title"><div className="library-section-heading"><h2 id="templates-title">القوالب</h2><p>{templates.length} اتجاهات متاحة في هذا التصنيف</p></div>{templates.length === 0 ? <p className="library-empty">لا توجد قوالب نشطة في هذا التصنيف حالياً.</p> : <PaperSurface><div className="library-template-grid">{templates.map((template, index) => <Reveal key={template.id} delay={(index % 4) * 80}><LibraryTemplateCard template={template} /></Reveal>)}</div></PaperSurface>}</section>
            <section className="library-section library-collections" aria-labelledby="collections-title"><div className="library-section-heading"><h2 id="collections-title">مجموعات العناصر</h2><p>يمكن إضافة المجموعة كاملة، ثم تعديل كل عنصر بمفرده.</p></div><div className="library-collection-grid">{collections.map((collection, index) => <Reveal key={collection.id} delay={(index % 3) * 100}><LibraryCollectionCard collection={collection} /></Reveal>)}</div></section>
            <aside className="library-footer"><img src="/brand/logos/methaq-symbol-small-flat.svg" alt="" /><p>هذه معاينات منخفضة التفاصيل. الأصول الأصلية تبقى داخل التخزين الخاص وتفتح للمحرر حسب الصلاحية.</p></aside>
        </div>
    </PublicLayout>;
}
