import type { AudioConfig } from '../../../Types/SceneConfig';
import type { EditorAsset } from '../../../Types/Editor';
export function SceneAudioPanel({ audio, assets, onChange }: { audio: AudioConfig; assets: EditorAsset[]; onChange: (audio: AudioConfig) => void }) {
    const tracks = assets.filter((asset) => asset.type === 'audio');
    return <fieldset className="scene-effects-panel"><legend>صوت الدعوة</legend><div>
        <label>المقطع<select value={audio.enabled ? audio.asset?.assetId ?? '' : ''} onChange={(event) => {
            const track = tracks.find((item) => item.id === event.target.value);
            onChange({ ...audio, enabled: Boolean(track), asset: track ? { assetId: track.id, version: track.version } : null });
        }}><option value="">بدون صوت</option>{tracks.map((track) => <option key={track.id} value={track.id}>{track.name}</option>)}</select></label>
        {tracks.length === 0 && <p>لا توجد مقاطع في المكتبة حالياً.</p>}
        {audio.enabled && <label>مستوى الصوت · {Math.round(audio.volume * 100)}%<input type="range" min={0} max={1} step={.05} value={audio.volume} onChange={(event) => onChange({ ...audio, volume: Number(event.target.value) })} /></label>}
        <small>يبدأ الصوت بعد نقرة الضيف، ويمكن إيقافه من المعاينة.</small>
    </div></fieldset>;
}
