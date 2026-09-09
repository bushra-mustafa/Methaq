import { Link, usePage } from '@inertiajs/react';
import type { LibraryTemplate } from '../../../Types/Library';
import type { SharedPageProps } from '../../../Types/PageProps';

interface LibraryTemplateCardProps {
    template: LibraryTemplate;
}

const categoryLabels: Record<LibraryTemplate['category'], string> = {
    wedding: 'زفاف',
    henna: 'حنّة',
    marriage_contract: 'عقد قران',
    graduation: 'تخرج',
};

export function LibraryTemplateCard({ template }: LibraryTemplateCardProps) {
    const { auth } = usePage<SharedPageProps>().props;
    const colors = Object.values(template.palette).slice(0, 6);
    const fontNames = template.assets.filter((asset) => asset.type === 'font').map((asset) => asset.name);
    const createUrl = `/app/events/create?template=${encodeURIComponent(template.slug)}`;

    return <article className="library-template-card">
        <div className="library-template-preview">
            <img src={template.thumbnailUrl} alt={`معاينة ${template.name}`} loading="lazy" />
            <span>{categoryLabels[template.category]}</span>
        </div>
        <div className="library-template-copy">
            <h2>{template.name}</h2>
            <div className="library-swatches" aria-label="ألوان القالب">{colors.map((color) => <i key={color} style={{ backgroundColor: color }} title={color} />)}</div>
            <p>{fontNames.length > 0 ? `خطوط مقترحة: ${fontNames.join('، ')}` : 'يمكن تركيب خطوط وعناصر من المكتبة.'}</p>
            <div className="library-card-actions"><Link href={auth.user ? createUrl : '/register'}>{auth.user ? 'استخدمي هذا القالب' : 'أنشئي حساباً للتصميم'}</Link><span>{template.assets.length} عناصر مقترحة</span></div>
        </div>
    </article>;
}
