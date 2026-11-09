import type { EnvelopeAppearance } from '../../../Types/SceneConfig';

interface Props {
    style: EnvelopeAppearance['sealStyle'];
    title?: string;
    disabled?: boolean;
    onOpen?: () => void;
}

export function InvitationSeal({ style, title, disabled, onOpen }: Props) {
    if (style === 'none') return null;
    const className = `invitation-seal is-${style} ${style.startsWith('wax') ? 'has-wax-finish' : ''}`;
    const mark = <i className="invitation-seal-logo" aria-hidden="true" />;
    return onOpen
        ? <button type="button" className={className} onClick={onOpen} disabled={disabled} aria-label={`فتح دعوة ${title ?? ''}`}>{mark}</button>
        : <span className={className} aria-hidden="true">{mark}</span>;
}
