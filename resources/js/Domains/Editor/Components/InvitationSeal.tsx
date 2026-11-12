import type { Ref } from 'react';
import type { EnvelopeAppearance } from '../../../Types/SceneConfig';
import '../../../../css/envelope.css';

interface Props {
    style: EnvelopeAppearance['sealStyle'];
    title?: string;
    disabled?: boolean;
    onOpen?: () => void;
    ref?: Ref<HTMLButtonElement>;
}

/** The Methaq seal: the shape and colour change, the Methaq logo inside never does. */
export function InvitationSeal({ style, title, disabled, onOpen, ref }: Props) {
    if (style === 'none') return null;
    const className = `invitation-seal is-${style} ${style.startsWith('wax') ? 'has-wax-finish' : ''}`;
    const mark = <i className="invitation-seal-logo" aria-hidden="true" />;
    return onOpen
        ? <button ref={ref} type="button" className={className} onClick={onOpen} disabled={disabled} aria-label={`فتح دعوة ${title ?? ''}`}>{mark}</button>
        : <span className={className} aria-hidden="true">{mark}</span>;
}
