import type { CSSProperties } from 'react';
import type { Palette } from '../../../Types/Palette';
import type { EnvelopeConfig, InvitationCover } from '../../../Types/SceneConfig';
import { resolveColor } from '../Services/editorDocument';
import { DEFAULT_ENVELOPE_APPEARANCE } from '../Services/scenePresentation';
import { InvitationSeal } from './InvitationSeal';
import '../../../../css/envelope-variants.css';

interface Props { cover?: InvitationCover; envelope: EnvelopeConfig; palette: Palette; cardUrl?: string; title?: string; opening?: boolean; namesPreview?: boolean; onOpen?: () => void }

export function InvitationEnvelope({ cover, envelope, palette, cardUrl, title, opening, namesPreview, onOpen }: Props) {
    const appearance = envelope.appearance ?? DEFAULT_ENVELOPE_APPEARANCE;
    const style = {
        '--envelope-paper': resolveColor(envelope.paperColor, { palette }),
        '--envelope-lining': resolveColor(envelope.liningColor, { palette }),
        '--seal-color': resolveColor(envelope.sealColor, { palette }),
        '--seal-x': `${appearance.sealX}%`, '--seal-y': `${appearance.sealY}%`, '--seal-size': `${appearance.sealSize}%`,
    } as CSSProperties;
    return <div className={`invitation-envelope is-${appearance.style} ${opening ? 'is-opening' : ''} ${namesPreview ? 'is-names-preview' : ''}`} style={style}>
        <div className="invitation-envelope-back" />
        <div className="invitation-envelope-letter" aria-hidden="true">{cover ? <div className={`invitation-letter-teaser is-${cover.language}`}><span>♡</span><strong dir="auto">{cover.names}</strong><small dir="auto">{cover.dateLabel}</small></div> : cardUrl && <img src={cardUrl} alt="" />}</div>
        <div className="invitation-envelope-front" />
        <div className="invitation-envelope-fold" />
        <div className="invitation-envelope-flap" />
        <InvitationSeal style={appearance.sealStyle} onOpen={onOpen} disabled={opening} title={title} />
    </div>;
}
