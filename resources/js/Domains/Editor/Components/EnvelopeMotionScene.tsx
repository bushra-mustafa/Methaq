import { useEffect, useRef } from 'react';
import type { EnvelopeMotionSceneProps } from '../../../Types/EnvelopeMotion';
import { useEnvelopeMotion } from '../Hooks/useEnvelopeMotion';
import { useEnvelopePreviewAssets } from '../Hooks/useEnvelopePreviewAssets';
import { ClassicIvoryEnvelope } from './ClassicIvoryEnvelope';

export default function EnvelopeMotionScene(props: EnvelopeMotionSceneProps) {
    const { cardUrl, title, names, motionPolicy } = props;
    const assets = useEnvelopePreviewAssets(cardUrl, names);
    const motion = useEnvelopeMotion(assets.status === 'ready', motionPolicy);
    const openButton = useRef<HTMLButtonElement>(null);
    const heading = useRef<HTMLHeadingElement>(null);
    const previousPhase = useRef(motion.phase);

    useEffect(() => {
        if (previousPhase.current === motion.phase) return;
        if (motion.phase === 'opened') heading.current?.focus({ preventScroll: true });
        if (motion.phase === 'sealed') openButton.current?.focus({ preventScroll: true });
        previousPhase.current = motion.phase;
    }, [motion.phase]);

    const replay = (): void => {
        motion.replay();
        motion.root.current?.closest('.envelope-motion-surround')?.scrollTo({ top: 0 });
    };

    return <div className="envelope-motion-scene" ref={motion.root} data-phase={motion.phase} data-paused={motion.paused}>
        <div className="envelope-motion-tools">
            <img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="ميثاق" />
            <div>
                <button type="button" className="envelope-motion-icon" aria-label={motion.paused ? 'استئناف الحركة' : 'إيقاف الحركة'} title={motion.paused ? 'استئناف الحركة' : 'إيقاف الحركة'} aria-pressed={motion.paused} disabled={!motion.running || motion.reduced} onClick={motion.togglePause}><span aria-hidden="true">{motion.paused ? '▶' : 'Ⅱ'}</span></button>
                <button type="button" className="envelope-motion-icon" aria-label="إعادة تجربة الفتح" title="إعادة تجربة الفتح" disabled={assets.status !== 'ready'} onClick={replay}><span aria-hidden="true">↺</span></button>
            </div>
        </div>
        {assets.status !== 'ready' && <div className="envelope-motion-notice" role={assets.status === 'error' ? 'alert' : 'status'}>
            <p>{assets.status === 'error' ? 'تعذّر تجهيز البطاقة والخطوط. أعيدي المحاولة لفتح الدعوة.' : 'نجهّز بطاقتك والخطوط…'}</p>
            {assets.status === 'error' && <button type="button" onClick={assets.retry}>إعادة المحاولة</button>}
        </div>}
        <div className="envelope-motion-stage" aria-busy={assets.status === 'loading'}>
            <div className="envelope-motion-cover" inert={motion.phase !== 'sealed' || assets.status !== 'ready'} aria-hidden={motion.phase === 'opened'}>
                <header data-envelope-part="cover"><p>لِلحَظاتٍ تَبقى</p><h2 dir="auto">{title}</h2><span>دعوة تُفتح بكلّ حب</span></header>
                <ClassicIvoryEnvelope {...props} onOpen={motion.open} disabled={motion.phase !== 'sealed' || assets.status !== 'ready'} />
                <div className="envelope-motion-prompt" data-envelope-part="cover">
                    <button ref={openButton} type="button" onClick={motion.open} disabled={motion.phase !== 'sealed' || assets.status !== 'ready'}>افتح الدعوة <span aria-hidden="true">↗</span></button>
                    <small>{motion.reduced ? 'ستظهر البطاقة مباشرة حسب إعداد تقليل الحركة' : 'اضغطي على الختم لتبدأ الحكاية'}</small>
                </div>
            </div>
            <article className="envelope-motion-final" data-envelope-part="final" aria-hidden={motion.phase !== 'opened'} inert={motion.phase !== 'opened'}>
                <h2 ref={heading} tabIndex={-1} dir="auto">{title}</h2>
                <img src={cardUrl} alt={`بطاقة ${title}`} />
                <p>بحضوركم تكتمل فرحتنا</p>
            </article>
        </div>
        {motion.phase !== 'opened' && <button className="envelope-motion-skip" type="button" onClick={motion.skip} disabled={assets.status !== 'ready'}>عرض البطاقة مباشرة</button>}
        <p className="envelope-motion-signature">ميثاق · عَهْدُ الفرح</p>
        <span className="envelope-motion-sr" role="status">{motion.paused ? 'الحركة متوقفة مؤقتاً' : motion.phase === 'names-preview' ? 'تظهر الأسماء' : motion.phase === 'opened' ? 'تم فتح الدعوة' : motion.running ? 'جاري فتح الظرف' : 'الدعوة مغلقة'}</span>
    </div>;
}
