import { useState } from 'react';
import type { CSSProperties } from 'react';
import type { PresentationCard } from '../../../Types/PresentationCard';

interface Props { card: PresentationCard; fallbackUrl: string; title: string }
export function AnimatedInvitationCard({ card, fallbackUrl, title }: Props) {
    const [failed, setFailed] = useState(false);
    if (failed) return <img className="invitation-card-image" src={fallbackUrl} alt={title} />;
    let textIndex = 0;
    return <div className="invitation-layered-card" role="img" aria-label={title} style={{ aspectRatio: `${card.width}/${card.height}`, background: card.background }}>
        {card.parts.map((part, index) => <img key={index} src={part.url} alt="" onError={() => setFailed(true)} className={part.text ? 'is-text' : ''} style={{
            left: `${part.x / card.width * 100}%`, top: `${part.y / card.height * 100}%`, width: `${part.width / card.width * 100}%`, height: `${part.height / card.height * 100}%`,
            '--text-delay': `${part.text ? Math.min(textIndex++ * .18, 1.1) : 0}s`,
        } as CSSProperties} />)}
    </div>;
}
