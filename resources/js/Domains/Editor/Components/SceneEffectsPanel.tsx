import type { EffectConfig, EffectId } from '../../../Types/SceneConfig';
import type { HexColor, Palette } from '../../../Types/Palette';
import { resolveColor } from '../Services/editorDocument';
import { EFFECT_REGISTRY } from '../Services/designContract';

interface Props { effects: EffectConfig[]; palette: Palette; onChange: (effects: EffectConfig[]) => void }

export function SceneEffectsPanel({ effects, palette, onChange }: Props) {
    const update = (effect: EffectConfig, changes: Partial<EffectConfig>): void => {
        const next = { ...effect, ...changes };
        onChange(effects.some((item) => item.effectId === effect.effectId)
            ? effects.map((item) => item.effectId === effect.effectId ? next : item)
            : [...effects, next]);
    };
    return <fieldset className="scene-effects-panel"><legend>الأجواء حول الدعوة</legend>
        {(['sparkle', 'smoke'] as const).map((id: EffectId) => {
            const effect = effects.find((item) => item.effectId === id) ?? { effectId: id, effectVersion: 1, enabled: false, color: { source: 'palette', role: 'effect' }, intensity: .35, speed: 1 } satisfies EffectConfig;
            const label = id === 'sparkle' ? 'لمعة ناعمة' : 'ضباب خفيف';
            const limits = EFFECT_REGISTRY[id];
            return <div key={id}>
                <label className="scene-effect-toggle"><input type="checkbox" checked={effect.enabled} onChange={(event) => update(effect, { enabled: event.target.checked })} />{label}</label>
                {effect.enabled && <>
                    <label className="scene-color-control">لون {label}<input type="color" value={resolveColor(effect.color, { palette })} onChange={(event) => update(effect, { color: { source: 'literal', value: event.target.value as HexColor } })} /></label>
                    <label>شدة {label}<input type="range" min={limits.intensity[0]} max={limits.intensity[1]} step={.05} value={effect.intensity} onChange={(event) => update(effect, { intensity: Number(event.target.value) })} /></label>
                    <label>سرعة {label}<input type="range" min={limits.speed[0]} max={limits.speed[1]} step={.05} value={effect.speed} onChange={(event) => update(effect, { speed: Number(event.target.value) })} /></label>
                </>}
            </div>;
        })}
    </fieldset>;
}
