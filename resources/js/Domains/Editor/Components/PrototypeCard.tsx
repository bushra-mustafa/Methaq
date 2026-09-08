import { useState } from 'react';
import type { CSSProperties } from 'react';
import type { PrototypeDesign, TextSlot, TextPosition } from '../../../Types/PrototypeDesign';
import { ReferenceAtmosphere } from './ReferenceAtmosphere';
import { usePrototypeCanvas } from '../Hooks/usePrototypeCanvas';

interface Props { design: PrototypeDesign; onMove?: (slot: TextSlot, position: TextPosition) => void; exportable?: boolean }
export function PrototypeCard({ design, onMove, exportable = false }: Props) {
    const { host, ready, error, exportPreview } = usePrototypeCanvas(design, onMove);
    const [exportedImage, setExportedImage] = useState<string | null>(null);
    const [exportError, setExportError] = useState('');
    function download() {
        try {
            setExportedImage(exportPreview());
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
        {exportable && <><button type="button" disabled={!ready} onClick={download}>تصدير ومعاينة الصورة</button><small>صورة ثابتة منخفضة الدقة؛ الحركة تظهر في معاينة الضيف.</small></>}
        {exportError && <p role="alert">{exportError}</p>}
        {exportedImage && <section className="lab-export-result" aria-label="الصورة بعد التصدير">
            <h2>الصورة بعد التصدير</h2>
            <p>هذه نسخة ثابتة وقت التصدير، بمقاس 360 × 640 بكسل وبعلامة مائية. اللمعة المتحركة لا تدخل في الصورة.</p>
            <img src={exportedImage} alt="بطاقة ميثاق المصدرة بعلامة مائية" width={360} height={640} />
            <a href={exportedImage} download="methaq-watermarked-preview.png">حفظ صورة PNG</a>
            <button type="button" onClick={() => setExportedImage(null)}>إغلاق نتيجة التصدير</button>
        </section>}
    </div>;
}
