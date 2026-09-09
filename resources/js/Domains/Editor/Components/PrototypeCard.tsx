import { useState } from 'react';
import type { CSSProperties } from 'react';
import type { PrototypeDesign, TextSlot, TextPosition } from '../../../Types/PrototypeDesign';
import { ReferenceAtmosphere } from './ReferenceAtmosphere';
import { usePrototypeCanvas } from '../Hooks/usePrototypeCanvas';

interface Props { design: PrototypeDesign; onMove?: (slot: TextSlot, position: TextPosition) => void; exportable?: boolean }
export function PrototypeCard({ design, onMove, exportable = false }: Props) {
    const { host, ready, error, exportPreview } = usePrototypeCanvas(design, onMove);
    const [exportedImage, setExportedImage] = useState<{ url: string; width: number; height: number; quality: 'preview' | '4k' } | null>(null);
    const [zoomed, setZoomed] = useState(false);
    const [exportError, setExportError] = useState('');
    function download(quality: 'preview' | '4k') {
        try {
            setExportedImage({ url: exportPreview(quality), width: quality === '4k' ? 2160 : 360, height: quality === '4k' ? 3840 : 640, quality });
            setZoomed(false);
            setExportError('');
        } catch {
            setExportError('تعذّر تصدير الصورة. حاول مرة أخرى.');
        }
    }
    return <div className="lab-card-wrap">
        <div className={`lab-card ${design.motion ? '' : 'motion-off'}`} style={{ '--sparkle': design.sparkle, '--sparkle-intensity': design.intensity } as CSSProperties}>
            <div ref={host} className="lab-canvas" />
            {ready && <ReferenceAtmosphere color={design.sparkle} intensity={design.intensity} motion={design.motion} />}
            {(!ready || error) && <p className="lab-loading" role="status">{error || 'جاري تحميل الخطوط والمعاينة…'}</p>}
        </div>
        {exportable && <><button type="button" disabled={!ready} onClick={() => download('preview')}>تصدير معاينة خفيفة</button><button type="button" disabled={!ready} onClick={() => download('4k')}>تصدير بأعلى جودة · 4K</button><small>صورة PNG ثابتة؛ الحركة تظهر في معاينة الضيف.</small></>}
        {exportError && <p role="alert">{exportError}</p>}
        {exportedImage && <section className="lab-export-result" aria-label="الصورة بعد التصدير">
            <h2>الصورة بعد التصدير</h2>
            <p>هذه نسخة ثابتة وقت التصدير، بمقاس {exportedImage.width} × {exportedImage.height} بكسل. اللمعة المتحركة لا تدخل في الصورة.</p>
            <div className={`lab-export-image ${zoomed ? 'is-zoomed' : ''}`} tabIndex={0} aria-label="معاينة الصورة؛ يمكن التمرير عند التكبير"><img src={exportedImage.url} alt="بطاقة ميثاق المصدرة" width={exportedImage.width} height={exportedImage.height} /></div>
            <button type="button" aria-pressed={zoomed} onClick={() => setZoomed(!zoomed)}>{zoomed ? 'عرض الصورة كاملة' : 'تكبير لفحص التفاصيل'}</button>
            <a href={exportedImage.url} download={`methaq-${exportedImage.quality}-${exportedImage.width}x${exportedImage.height}.png`}>حفظ صورة PNG</a>
            <button type="button" onClick={() => setExportedImage(null)}>إغلاق نتيجة التصدير</button>
        </section>}
    </div>;
}
