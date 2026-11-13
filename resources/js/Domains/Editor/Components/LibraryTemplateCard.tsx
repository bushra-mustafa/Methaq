import { Link, usePage } from '@inertiajs/react';
import { trackEvent } from '../../Privacy/analytics';
import Thumbnail from '../../../Components/Thumbnail';
import { categoryLabels } from '../../../Types/Library';
import type { LibraryTemplate } from '../../../Types/Library';
import type { SharedPageProps } from '../../../Types/PageProps';

interface LibraryTemplateCardProps {
    template: LibraryTemplate;
}

export function LibraryTemplateCard({ template }: LibraryTemplateCardProps) {
    const { auth } = usePage<SharedPageProps>().props;
    const colors = [...new Set(Object.values(template.palette))].slice(0, 6);
    const fontNames = template.assets.filter((asset) => asset.type === 'font').map((asset) => asset.name);
    const startUrl = `/start/${encodeURIComponent(template.slug)}`;

    return <article className="library-template-card">
        <div className="library-template-preview">
            <Thumbnail src={template.thumbnailUrl} avif={template.thumbnailAvifUrl} webp={template.thumbnailWebpUrl} alt={`معاينة قالب ${template.name}`} width={540} height={960} />
            <span>{categoryLabels[template.category]}</span>
        </div>
        <div className="library-template-copy">
            <h2>{template.name}</h2>
            <div className="library-swatches" aria-label="ألوان القالب">{colors.map((color) => <i key={color} style={{ backgroundColor: color }} title={color} />)}</div>
            <p>{fontNames.length > 0 ? `خطوط مقترحة: ${fontNames.join('، ')}` : 'يمكن تركيب خطوط وعناصر من المكتبة.'}</p>
            <div className="library-card-actions"><Link href={startUrl} onClick={() => trackEvent('template_start')}>{auth.user ? 'استخدمي هذا القالب' : 'أنشئي حساباً للتصميم'}</Link><span>{template.assets.length} عناصر مقترحة</span></div>
        </div>
    </article>;
}
