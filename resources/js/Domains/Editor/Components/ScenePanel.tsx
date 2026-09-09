import { SceneCoverPanel } from './SceneCoverPanel';
import { SceneAudioPanel } from './SceneAudioPanel';
import { SceneBackdropPicker } from './SceneBackdropPicker';
import type { EditorAsset } from '../../../Types/Editor';
import { SceneEffectsPanel } from './SceneEffectsPanel';
import { EnvelopeStylePicker } from './EnvelopeStylePicker';
import type { ColorValue, HexColor, Palette } from '../../../Types/Palette';
import type { EnvelopeAppearance, EnvelopeConfig, SceneConfig } from '../../../Types/SceneConfig';
import { resolveColor } from '../Services/editorDocument';
import { changeOpeningType, createEnvelope, DEFAULT_ENVELOPE_APPEARANCE } from '../Services/scenePresentation';

interface Props { assets: EditorAsset[]; cardUrl?: string; scene: SceneConfig; palette: Palette; onChange: (scene: SceneConfig) => void; onPreview: () => void; previewReady: boolean }

export function ScenePanel({ assets, scene, palette, onChange, onPreview, previewReady, cardUrl }: Props) {
    const envelope = scene.opening.type === 'envelope' ? scene.opening.envelope : null;
    const previewEnvelope = envelope ?? createEnvelope();
    const appearance = envelope?.appearance ?? DEFAULT_ENVELOPE_APPEARANCE;
    const updateEnvelope = (changes: Partial<EnvelopeConfig>): void => {
        if (scene.opening.type !== 'envelope') return;
        onChange({ ...scene, opening: { ...scene.opening, envelope: { ...scene.opening.envelope, ...changes } } });
    };
    const updateAppearance = (changes: Partial<EnvelopeAppearance>): void => updateEnvelope({ appearance: { ...appearance, ...changes } });

    return <section className="editor-panel-section scene-panel">
        <header><span aria-hidden="true">✉</span><div><h2>العرض والفتح</h2><p>جهّزي اللحظة الأولى التي يراها ضيوفك.</p></div></header>
        <div className="editor-control-stack">
            <EnvelopeStylePicker cardUrl={cardUrl} envelope={previewEnvelope} palette={palette} active={envelope !== null} onChange={(style) => {
                if (envelope) { updateAppearance({ style }); return; }
                onChange({ ...scene, opening: { type: 'envelope', durationMs: scene.opening.durationMs || 1800, envelope: { ...previewEnvelope, appearance: { ...appearance, style } } } });
            }} />
            <fieldset className="scene-choice-group"><legend>كيف تبدأ الدعوة؟</legend>
                {([{ value: 'envelope', label: 'ظرف بختم', detail: 'يفتح الضيف الختم لتظهر البطاقة' }, { value: 'fade', label: 'ظهور ناعم', detail: 'غلاف بسيط يتلاشى عند الفتح' }, { value: 'direct', label: 'عرض مباشر', detail: 'تظهر البطاقة فوراً' }] as const).map((option) => <label key={option.value} className={scene.opening.type === option.value ? 'is-selected' : ''}><input type="radio" name="opening-type" checked={scene.opening.type === option.value} onChange={() => onChange({ ...scene, opening: changeOpeningType(scene.opening, option.value) })} /><span><strong>{option.label}</strong><small>{option.detail}</small></span></label>)}
            </fieldset>
            <SceneBackdropPicker backdrop={scene.backdrop} onChange={(backdrop) => onChange({ ...scene, backdrop })} />
            {envelope && <>
                <h3>الظرف</h3>
                <SceneColorControl label="لون الظرف" value={envelope.paperColor} palette={palette} onChange={(paperColor) => updateEnvelope({ paperColor })} />
                <SceneColorControl label="لون البطانة" value={envelope.liningColor} palette={palette} onChange={(liningColor) => updateEnvelope({ liningColor })} />
                <h3>الختم</h3>
                <label>شكل الختم<select value={appearance.sealStyle} onChange={(event) => updateAppearance({ sealStyle: event.target.value as EnvelopeAppearance['sealStyle'] })}><option value="wax">ختم شمعي</option><option value="medallion">ختم دائري معدني</option><option value="methaq">ختم ميثاق</option></select></label>
                <SceneColorControl label="لون الختم" value={envelope.sealColor} palette={palette} onChange={(sealColor) => updateEnvelope({ sealColor })} />
                {appearance.sealStyle !== 'methaq' && <label>الأحرف أو النص داخل الختم<input value={envelope.monogram} maxLength={12} dir="auto" onChange={(event) => updateEnvelope({ monogram: event.target.value })} /></label>}
                <label>الموضع الأفقي · {appearance.sealX}% من اليسار<input type="range" min={20} max={80} value={appearance.sealX} onChange={(event) => updateAppearance({ sealX: Number(event.target.value) })} /></label>
                <label>الموضع العمودي · {appearance.sealY}% من الأعلى<input type="range" min={25} max={75} value={appearance.sealY} onChange={(event) => updateAppearance({ sealY: Number(event.target.value) })} /></label>
                <label>حجم الختم · {appearance.sealSize}% من عرض الظرف<input type="range" min={14} max={26} value={appearance.sealSize} onChange={(event) => updateAppearance({ sealSize: Number(event.target.value) })} /></label>
                <button className="scene-secondary-button" type="button" onClick={() => updateAppearance({ sealX: 50, sealY: 52, sealSize: 20 })}>إرجاع الختم للمنتصف</button>
            </>}
            {scene.opening.type !== 'direct' && <label>مدة الفتح · {(scene.opening.durationMs / 1000).toFixed(1)} ثانية<input type="range" min={100} max={5000} step={100} value={scene.opening.durationMs} onChange={(event) => { if (scene.opening.type !== 'direct') onChange({ ...scene, opening: { ...scene.opening, durationMs: Number(event.target.value) } }); }} /></label>}
            <SceneCoverPanel cover={scene.cover} onChange={(cover) => onChange({ ...scene, cover })} />
            <SceneAudioPanel audio={scene.audio} assets={assets} onChange={(audio) => onChange({ ...scene, audio })} />
            <SceneEffectsPanel effects={scene.effects} palette={palette} onChange={(effects) => onChange({ ...scene, effects })} />
            <label>الحركة<select value={scene.motionPolicy} onChange={(event) => onChange({ ...scene, motionPolicy: event.target.value as SceneConfig['motionPolicy'] })}><option value="system">حسب تفضيلات جهاز الضيف</option><option value="reduced">حركة مخففة</option><option value="off">بدون حركة</option></select></label>
            <button type="button" className="scene-preview-button" disabled={!previewReady} onClick={onPreview}>معاينة كضيف ↗</button>
            <p className="scene-panel-note">ألوان الظرف والختم مستقلة عن البطاقة. تُحفظ خيارات العرض تلقائياً مع التصميم.</p>
        </div>
    </section>;
}

function SceneColorControl({ label, value, palette, onChange }: { label: string; value: ColorValue; palette: Palette; onChange: (color: ColorValue) => void }) {
    return <label className="scene-color-control"><span>{label}</span><input type="color" value={resolveColor(value, { palette })} onChange={(event) => onChange({ source: 'literal', value: event.target.value as HexColor })} /></label>;
}
