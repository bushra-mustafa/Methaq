import type { CSSProperties } from 'react';
import type { Palette } from '../../../Types/Palette';
import type { EffectConfig } from '../../../Types/SceneConfig';
import { resolveColor } from '../Services/editorDocument';

interface Props { effects: EffectConfig[]; palette: Palette }

export function InvitationAtmosphere({ effects, palette }: Props) {
    return <div className="invitation-atmosphere" aria-hidden="true">
        {effects.filter((effect) => effect.enabled && effect.intensity > 0).map((effect) => {
            const count = effect.effectId === 'smoke' ? 3 : Math.ceil(effect.intensity * 20);
            return <div key={effect.effectId} className={`invitation-effect is-${effect.effectId}`} style={{
                '--effect-color': resolveColor(effect.color, { palette }),
                '--effect-opacity': effect.intensity,
            } as CSSProperties}>
                {Array.from({ length: count }, (_, index) => <i key={index} style={{
                    left: `${(index * 37 + 13) % 100}%`, top: `${(index * 23 + 17) % 100}%`,
                    '--drift-duration': `${(9 + index % 5 * 2) / effect.speed}s`,
                    '--drift-delay': `${-index * 1.7}s`,
                } as CSSProperties} />)}
            </div>;
        })}
    </div>;
}
