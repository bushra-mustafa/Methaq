import { useEffect, useRef, useState } from 'react';
import type { CSSProperties } from 'react';
import { Heart, LoaderCircle, Pause, Play, RotateCcw, Volume2, VolumeX } from 'lucide-react';
import type { EditorAsset } from '../../../Types/Editor';
import type { Palette } from '../../../Types/Palette';
import type { PresentationCard } from '../../../Types/PresentationCard';
import type { EnvelopeConfig, SceneConfig } from '../../../Types/SceneConfig';
import { resolveColor } from '../Services/editorDocument';
import { useInvitationOpening } from '../Hooks/useInvitationOpening';
import { useInvitationAudio } from '../Hooks/useInvitationAudio';
import { AnimatedInvitationCard } from './AnimatedInvitationCard';
import { InvitationAmbience } from './InvitationAmbience';
import '../../../../css/invitation-noir.css';

interface Props {
    audioAllowed: boolean;
    assets: EditorAsset[];
    cardParts?: PresentationCard | null;
    scene: SceneConfig;
    palette: Palette;
    cardUrl: string;
    title: string;
    viewport: 'phone' | 'desktop';
    manageFocus: boolean;
}

const LATIN = /[A-Za-z]/;
const ARABIC = /[\u0600-\u06FF]/;

function envelopeStyle(envelope: EnvelopeConfig | null, palette: Palette): CSSProperties {
    const document = { palette };

    return {
        '--noir-envelope': envelope ? resolveColor(envelope.paperColor, document) : '#71334a',
        '--noir-lining': envelope ? resolveColor(envelope.liningColor, document) : '#fff8ea',
        '--noir-seal': envelope ? resolveColor(envelope.sealColor, document) : '#d8b982',
    } as CSSProperties;
}

/**
 * «ليلة عنابية»: the high-fidelity guest presentation for the `henna-noir` backdrop.
 * Every visible word comes from the event's scene cover; the opened view shows the
 * card designed in the editor.
 */
export function NoirInvitationScene({ audioAllowed, assets, cardParts, scene, palette, cardUrl, title, viewport, manageFocus }: Props) {
    const root = useRef<HTMLElement>(null);
    const heading = useRef<HTMLHeadingElement>(null);
    const opener = useRef<HTMLButtonElement>(null);
    const [imageFailed, setImageFailed] = useState(false);
    const { phase, motion, paused, motionAvailable, open, skip, replay, toggleMotion } = useInvitationOpening(scene.opening, scene.motionPolicy);
    const audio = useInvitationAudio(audioAllowed ? scene.audio : { ...scene.audio, enabled: false }, assets);
    const cover = scene.cover;
    const envelope = scene.opening.type === 'envelope' ? scene.opening.envelope : null;
    const names = cover?.names || title;
    const namesAreLatin = LATIN.test(names);
    const dateLabel = cover?.dateLabel ?? '';
    const dateDirection = ARABIC.test(dateLabel) ? 'rtl' : 'ltr';
    const visualPhase = phase === 'names-preview' ? 'opening' : phase;
    const soundActive = audio.state === 'playing' || audio.state === 'loading';
    const soundProblem = audio.state === 'blocked' || audio.state === 'error';

    useEffect(() => {
        if (phase === 'names-preview') {
            skip();
        }
    }, [phase, skip]);

    useEffect(() => {
        if (!manageFocus) {
            return;
        }
        if (phase === 'opened') {
            heading.current?.focus({ preventScroll: true });
        } else if (phase === 'sealed') {
            opener.current?.focus({ preventScroll: true });
        }
    }, [phase, manageFocus]);

    const openInvitation = (): void => {
        void audio.play();
        open();
    };

    const replayInvitation = (): void => {
        audio.reset();
        replay();
    };

    return <section
        ref={root}
        className={`invitation-player invitation-noir is-${viewport} ${motion ? '' : 'motion-off'}`}
        data-phase={visualPhase}
        style={{ ...envelopeStyle(envelope, palette), '--opening-duration': `${scene.opening.durationMs}ms` } as CSSProperties}
        dir="rtl"
        aria-label={title}
    >
        <InvitationAmbience preset="henna-noir" paused={paused || !motion} interactionRoot={root} />

        <header className="invitation-noir-header">
            <span className="invitation-noir-occasion" dir="auto">{title}</span>
            <span className={`invitation-noir-header-names ${namesAreLatin ? 'is-latin' : ''}`} dir="auto">{names}</span>
            <div className="invitation-noir-controls">
                {phase === 'opened' && scene.opening.type !== 'direct' && <button type="button" className="invitation-noir-icon" onClick={replayInvitation} aria-label="إعادة فتح الدعوة" title="إعادة فتح الدعوة"><RotateCcw aria-hidden="true" /></button>}
                {motionAvailable && <button type="button" className="invitation-noir-icon" onClick={toggleMotion} aria-pressed={paused} aria-label={paused ? 'متابعة الحركة' : 'إيقاف الحركة مؤقتاً'} title={paused ? 'متابعة الحركة' : 'إيقاف الحركة مؤقتاً'}>{paused ? <Play aria-hidden="true" /> : <Pause aria-hidden="true" />}</button>}
            </div>
        </header>

        {phase !== 'opened' ? <div className="invitation-noir-entry">
            <div className="invitation-noir-frame" aria-hidden="true" />
            <div className="invitation-noir-entry-inner">
                <p className="invitation-noir-kicker">بِكُلِّ الحُبّ</p>
                <div className="invitation-noir-entry-center">
                    <h2 className="invitation-noir-title" dir="auto">{cover?.heading || title}</h2>
                    {envelope && <div className="invitation-noir-envelope-scene">
                        <div className="invitation-noir-halo" aria-hidden="true" />
                        <div className="invitation-noir-envelope">
                            <div className="invitation-noir-envelope-back" aria-hidden="true" />
                            <div className="invitation-noir-letter" aria-hidden="true">
                                <Heart strokeWidth={1} />
                                <span className={`invitation-noir-letter-names ${namesAreLatin ? 'is-latin' : ''}`} dir="auto">{names}</span>
                                {dateLabel && <span className="invitation-noir-letter-date" dir={dateDirection}>{dateLabel}</span>}
                            </div>
                            <div className="invitation-noir-envelope-front" aria-hidden="true" />
                            <div className="invitation-noir-envelope-fold" aria-hidden="true" />
                            <div className="invitation-noir-envelope-flap" aria-hidden="true" />
                            <button ref={opener} type="button" className="invitation-noir-seal" onClick={openInvitation} disabled={phase !== 'sealed'} aria-label={`افتح دعوة ${title}`}>
                                <span dir="auto">{envelope.monogram}</span>
                                <span className="invitation-noir-seal-ring" aria-hidden="true" />
                            </button>
                        </div>
                    </div>}
                    {!envelope && <button ref={opener} type="button" className="invitation-noir-open" onClick={openInvitation} disabled={phase !== 'sealed'}>افتح الدعوة</button>}
                    <p className={`invitation-noir-cover-names ${namesAreLatin ? 'is-latin' : ''}`} dir="auto">{names}</p>
                    {cover?.message && <p className="invitation-noir-blessing" dir="auto">{cover.message}</p>}
                </div>
                <div className="invitation-noir-entry-bottom">
                    {dateLabel && <span className="invitation-noir-date" dir={dateDirection}>{dateLabel}</span>}
                    {phase === 'sealed' && <button type="button" className="invitation-noir-skip" onClick={skip}>عرض البطاقة مباشرة</button>}
                </div>
            </div>
        </div> : <div className="invitation-noir-stage">
            <article className="invitation-noir-paper">
                <h1 ref={heading} tabIndex={-1} className="invitation-sr-only">{title}</h1>
                {!imageFailed
                    ? (cover?.animateText && cardParts
                        ? <AnimatedInvitationCard card={cardParts} fallbackUrl={cardUrl} title={title} />
                        : <img className="invitation-noir-card" src={cardUrl} alt={`بطاقة ${title}`} onError={() => setImageFailed(true)} />)
                    : <p role="alert" className="invitation-noir-card-error">تعذّر عرض صورة البطاقة. أعيدي فتح المعاينة بعد الحفظ.</p>}
            </article>
            <footer className="invitation-noir-footer" aria-hidden="true"><Heart size={15} strokeWidth={1} /></footer>
        </div>}

        {phase !== 'sealed' && (audio.available || audio.missing) && <div className="invitation-noir-sound">
            <button type="button" className="invitation-noir-icon is-sound" disabled={audio.missing} onClick={() => soundActive ? audio.pause() : void audio.play()} aria-label={soundActive ? 'إيقاف الموسيقى مؤقتاً' : 'تشغيل الموسيقى'} title={soundActive ? 'إيقاف الموسيقى مؤقتاً' : 'تشغيل الموسيقى'}>
                {audio.state === 'loading' ? <LoaderCircle className="invitation-noir-spin" aria-hidden="true" /> : soundActive ? <Volume2 aria-hidden="true" /> : <VolumeX aria-hidden="true" />}
            </button>
            <output className={soundProblem || audio.missing ? 'invitation-noir-sound-notice' : 'invitation-sr-only'}>
                {audio.missing ? 'المقطع غير متاح' : soundProblem ? 'اضغطوا على زر الصوت لتشغيل الموسيقى' : soundActive ? 'الموسيقى تعمل' : 'الموسيقى متوقفة'}
            </output>
        </div>}

        <span className="invitation-sr-only" role="status">{phase === 'opening' || phase === 'names-preview' ? 'جاري فتح الظرف' : phase === 'opened' ? `تم فتح دعوة ${title}` : 'الدعوة مغلقة'}</span>
    </section>;
}
