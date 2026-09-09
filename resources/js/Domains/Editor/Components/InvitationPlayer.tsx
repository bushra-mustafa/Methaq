import type { EditorAsset } from '../../../Types/Editor';
import type { PresentationCard } from '../../../Types/PresentationCard';
import { AnimatedInvitationCard } from './AnimatedInvitationCard';
import { InvitationCoverArt } from './InvitationCoverArt';
import { useInvitationAudio } from '../Hooks/useInvitationAudio';
import { useEffect, useRef, useState } from 'react';
import type { CSSProperties } from 'react';
import type { Palette } from '../../../Types/Palette';
import type { SceneConfig } from '../../../Types/SceneConfig';
import { useInvitationOpening } from '../Hooks/useInvitationOpening';
import { InvitationEnvelope } from './InvitationEnvelope';
import { InvitationAtmosphere } from './InvitationAtmosphere';
import '../../../../css/invitation.css';

interface Props { audioAllowed?: boolean; assets: EditorAsset[]; cardParts?: PresentationCard | null; scene: SceneConfig; palette: Palette; cardUrl: string; title: string; viewport?: 'phone' | 'desktop'; manageFocus?: boolean }

export function InvitationPlayer({ audioAllowed = true, assets, cardParts, scene, palette, cardUrl, title, viewport = 'desktop', manageFocus = true }: Props) {
    const heading = useRef<HTMLHeadingElement>(null);
    const openButton = useRef<HTMLButtonElement>(null);
    const [imageFailed, setImageFailed] = useState(false);
    const { phase, motion, paused, motionAvailable, open, skip, replay, toggleMotion } = useInvitationOpening(scene.opening, scene.motionPolicy);
    useEffect(() => {
        if (!manageFocus) return;
        if (phase === 'opened') heading.current?.focus({ preventScroll: true });
        else if (phase === 'sealed') openButton.current?.focus({ preventScroll: true });
    }, [phase, manageFocus]);
    const audio = useInvitationAudio(audioAllowed ? scene.audio : { ...scene.audio, enabled: false }, assets);
    const openWithAudio = (): void => { void audio.play(); open(); };
    const replayWithAudio = (): void => { audio.reset(); replay(); };
    const style = {
        '--invitation-background': palette.values.background,
        '--invitation-ink': palette.values.primaryText,
        '--invitation-accent': palette.values.accent,
        '--opening-duration': `${scene.opening.durationMs}ms`,
    } as CSSProperties;
    return <section className={`invitation-player is-${viewport} is-motion-${scene.motionPolicy} is-backdrop-${scene.backdrop?.preset ?? 'inherit'} ${motion ? '' : 'motion-off'}`} style={style} data-phase={phase} data-opening={scene.opening.type} dir="rtl" aria-label={title}>
                <InvitationAtmosphere effects={scene.effects} palette={palette} />
                <div className="invitation-player-tools"><span>بِكُلِّ الحُبّ</span>{motionAvailable ? <button type="button" onClick={toggleMotion} aria-pressed={paused}>{paused ? 'استئناف الحركة' : 'إيقاف الحركة'}</button> : <span>الحركة متوقفة</span>}</div>
                {(audio.available || audio.missing) && <div className="invitation-audio-control">
                    <button type="button" disabled={audio.missing} onClick={() => audio.state === 'playing' || audio.state === 'loading' ? audio.pause() : void audio.play()}>{audio.state === 'playing' ? 'إيقاف الصوت' : audio.state === 'loading' ? 'إلغاء تحميل الصوت' : 'تشغيل الصوت'}</button>
                    <span role="status">{audio.missing ? 'المقطع غير متاح' : audio.state === 'blocked' ? 'اضغطي تشغيل للسماح بالصوت' : audio.state === 'error' ? 'تعذّر تشغيل الصوت؛ يمكنك إعادة المحاولة' : ''}</span>
                </div>}
                {phase === 'sealed' || phase === 'opening' || phase === 'names-preview' ? <div className={`invitation-cover ${phase === 'names-preview' ? 'is-names-preview' : ''}`}>
                    <p className="invitation-cover-eyebrow">دعوة لحضور</p><h1 dir="auto">{scene.cover?.heading || title}</h1><p className="invitation-cover-intro">{scene.cover?.message ?? 'بحضوركم تكتمل فرحتنا'}</p>
                    {scene.opening.type === 'envelope' && <div className="invitation-envelope-scene"><InvitationCoverArt decoration={scene.cover?.decoration ?? 'halo'} palette={palette} /><InvitationEnvelope cover={scene.cover} envelope={scene.opening.envelope} palette={palette} cardUrl={cardUrl} title={title} opening={phase === 'opening' || phase === 'names-preview'} namesPreview={phase === 'names-preview'} onOpen={openWithAudio} /></div>}
                    {scene.opening.type === 'fade' && <div className="invitation-fade-cover" aria-hidden="true"><span>♡</span><p>دعوة بكل الحب</p></div>}
                    {scene.cover && phase !== 'names-preview' && <div className={`invitation-cover-names is-${scene.cover.language}`}><p dir="auto">{scene.cover.names}</p><small dir="auto">{scene.cover.dateLabel}</small></div>}
                    {phase !== 'names-preview' && <><button ref={openButton} className="invitation-open-button" type="button" onClick={openWithAudio} disabled={phase === 'opening'}>{phase === 'opening' ? 'تُفتح الدعوة…' : 'افتح الدعوة'}</button>
                    <button className="invitation-skip-button" type="button" onClick={skip}>عرض البطاقة مباشرة</button></>}
                </div> : <article className="invitation-opened">
                    <h1 tabIndex={-1} ref={heading}>{title}</h1>
                    {!imageFailed ? (scene.cover?.animateText && cardParts ? <AnimatedInvitationCard card={cardParts} fallbackUrl={cardUrl} title={title} /> : <img className="invitation-card-image" src={cardUrl} alt={`بطاقة ${title}`} onError={() => setImageFailed(true)} />) : <p role="alert">تعذّر عرض صورة البطاقة. ارجعي للتعديل وأعيدي فتح المعاينة.</p>}
                    <p>بحضوركم تكتمل فرحتنا</p>
                    {scene.opening.type !== 'direct' && <button type="button" onClick={replayWithAudio}>إعادة تجربة الفتح ↺</button>}
                </article>}
                <footer className="invitation-player-footer">ميثاق · عَهْدُ الفرح</footer>
                <span className="invitation-sr-only" role="status">{phase === 'opening' ? 'جاري فتح الظرف' : phase === 'names-preview' ? 'تظهر الأسماء' : phase === 'opened' ? 'تم فتح الدعوة' : 'الدعوة مغلقة'}</span>
            </section>;
}
