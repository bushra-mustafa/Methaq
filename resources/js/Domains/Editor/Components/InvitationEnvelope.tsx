import type { CSSProperties, ReactNode, Ref } from 'react';
import type { EnvelopeAppearance, InvitationCover } from '../../../Types/SceneConfig';
import type { EnvelopeColors } from '../Services/scenePresentation';
import { DEFAULT_ENVELOPE_APPEARANCE } from '../Services/scenePresentation';
import { InvitationSeal } from './InvitationSeal';
import '../../../../css/envelope.css';

export type EnvelopeState = 'closed' | 'open' | 'presenting';

interface Props {
    colors: EnvelopeColors;
    appearance?: EnvelopeAppearance;
    state?: EnvelopeState;
    /** What peeks out of the envelope: names/date (EnvelopeLetter) or the card image. */
    letter?: ReactNode;
    /** Invitation title, read in the seal button's accessible name. */
    sealLabel?: string;
    onSealClick?: () => void;
    sealRef?: Ref<HTMLButtonElement>;
}

/**
 * The single Methaq envelope. Every surface (editor pickers, guest player, scene themes
 * and the home page) renders this component and only decides when `state` changes.
 */
export function InvitationEnvelope({ colors, appearance = DEFAULT_ENVELOPE_APPEARANCE, state = 'closed', letter, sealLabel, onSealClick, sealRef }: Props) {
    const style = {
        '--envelope-paper': colors.paper,
        '--envelope-lining': colors.lining,
        '--seal-color': colors.seal,
        '--seal-x': `${appearance.sealX}%`, '--seal-y': `${appearance.sealY}%`, '--seal-size': `${appearance.sealSize}%`,
    } as CSSProperties;

    return <div className={`invitation-envelope is-${appearance.style}`} data-state={state} style={style}>
        <div className="invitation-envelope-back" />
        <div className="invitation-envelope-letter" aria-hidden="true">{letter}</div>
        <div className="invitation-envelope-front" />
        <div className="invitation-envelope-fold" />
        <div className="invitation-envelope-flap" />
        <InvitationSeal style={appearance.sealStyle} onOpen={onSealClick} disabled={state !== 'closed'} title={sealLabel} ref={sealRef} />
    </div>;
}

interface LetterProps {
    names: string;
    detail?: string;
    language?: InvitationCover['language'];
}

/** The names-and-date note inside the envelope. */
export function EnvelopeLetter({ names, detail, language = 'ar' }: LetterProps) {
    return <div className={`invitation-letter-teaser is-${language}`}>
        <span>♡</span>
        <strong dir="auto">{names}</strong>
        {detail && <small dir="auto">{detail}</small>}
    </div>;
}
