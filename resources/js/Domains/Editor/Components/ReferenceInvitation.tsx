import { useEffect, useRef, useState } from 'react';
import type { CSSProperties } from 'react';
import type { PrototypeDesign } from '../../../Types/PrototypeDesign';
import { floralImage } from '../Services/referenceArtwork';
import { ReferenceAtmosphere } from './ReferenceAtmosphere';
import { PrototypeCard } from './PrototypeCard';

interface Props { design: PrototypeDesign; onClose: () => void }
type Phase = 'sealed' | 'opening' | 'opened';
export function ReferenceInvitation({ design, onClose }: Props) {
    const dialog = useRef<HTMLDialogElement>(null);
    const heading = useRef<HTMLHeadingElement>(null);
    const sealButton = useRef<HTMLButtonElement>(null);
    const [phase, setPhase] = useState<Phase>(design.entrance === 'direct' ? 'opened' : 'sealed');
    const [paused, setPaused] = useState(!design.motion);
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const names = design.language === 'ar' ? design.arabicNames : design.englishNames;
    const direction = design.language === 'ar' ? 'rtl' : 'ltr';
    useEffect(() => {
        const element = dialog.current;
        element?.showModal();
        return () => { element?.close(); if (timer.current) clearTimeout(timer.current); };
    }, []);
    useEffect(() => { if (phase === 'opened') heading.current?.focus({ preventScroll: true }); }, [phase]);
    function finishOpening() { if (timer.current) clearTimeout(timer.current); timer.current = null; setPhase('opened'); }
    function open() {
        if (phase !== 'sealed') return;
        if (paused || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { finishOpening(); return; }
        setPhase('opening'); timer.current = setTimeout(finishOpening, 1700);
    }
    function replay() {
        if (timer.current) clearTimeout(timer.current);
        setPhase('sealed');
        requestAnimationFrame(() => sealButton.current?.focus());
    }
    const style = { '--scene-color': design.background, '--paper': design.paper, '--scene-ink': design.ink, '--scene-accent': design.accent, '--envelope-color': design.envelope } as CSSProperties;
    return <dialog ref={dialog} className={`reference-dialog ${paused ? 'motion-off' : ''}`} style={style} onCancel={(event) => { event.preventDefault(); onClose(); }} aria-label="معاينة الدعوة التفاعلية">
        <div className="reference-scene" data-phase={phase}>
            <ReferenceAtmosphere color={design.sparkle} intensity={design.intensity} motion={!paused} />
            <header className="reference-header"><span>دعوة بكل الحب</span><span className="reference-monogram" dir={direction}>{design.seal}</span><div><button onClick={() => { setPaused(!paused); if (phase === 'opening') finishOpening(); }} aria-pressed={paused}>{paused ? 'تشغيل الحركة' : 'إيقاف الحركة'}</button><button onClick={onClose}>رجوع للتعديل</button></div></header>
            <div className="reference-stage">
                {phase !== 'opened' ? <div className="reference-cover">
                    <p className="reference-eyebrow">بِكُلِّ الحُبّ</p><h1>فرحتنا تحلى بوجودكم</h1><p className="reference-subtitle">دعوة صغيرة… لفرحة كبيرة</p>
                    <div className="reference-envelope-scene">
                        <div className="reference-halo" />
                        {design.florals && <><img className="reference-florals reference-florals-left" src={floralImage(design.envelope, design.accent)} alt="" /><img className="reference-florals reference-florals-right" src={floralImage(design.envelope, design.accent)} alt="" /></>}
                        <div className="reference-envelope">
                            <div className="reference-envelope-back" />
                            <div className="reference-letter" aria-hidden="true"><span>♡</span><span className="reference-letter-names" dir={direction}>{names}</span><span>{design.dateLabel}</span></div>
                            <div className="reference-envelope-front" /><div className="reference-envelope-fold" /><div className="reference-envelope-flap" />
                            <button ref={sealButton} className="reference-seal" onClick={open} disabled={phase === 'opening'} aria-label="اضغط الختم لفتح الدعوة"><span dir={direction}>{design.seal}</span><i /></button>
                        </div>
                    </div>
                    <p className={`reference-cover-names ${design.language === 'ar' ? 'is-arabic' : ''}`} dir={direction}>{names}</p>
                    <p className="reference-dua">{design.blessing}</p><p className="reference-date">{design.dateLabel}</p>
                    <button className="reference-skip" onClick={finishOpening}>عرض البطاقة مباشرة</button>
                </div> : <article className="reference-opened"><h1 ref={heading} tabIndex={-1} className="reference-sr-only">دعوة {names} — {design.dateLabel}</h1><PrototypeCard design={{ ...design, motion: !paused }} /><p className="reference-preview-note">معاينة الدعوة · ميثاق</p><button onClick={replay}>إعادة فتح الظرف</button></article>}
            </div><footer className="reference-footer">بحضوركم تكتمل فرحتنا ♡</footer>
            <span className="reference-sr-only" role="status">{phase === 'opening' ? 'جاري فتح الظرف' : phase === 'opened' ? 'تم فتح الدعوة' : ''}</span>
        </div>
    </dialog>;
}
