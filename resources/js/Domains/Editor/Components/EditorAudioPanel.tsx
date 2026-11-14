import type { EditorAsset } from '../../../Types/Editor';
import type { AudioConfig } from '../../../Types/SceneConfig';
import { useTrackSampler } from '../Hooks/useTrackSampler';
import { audioTracks, chooseTrack, selectedTrackId } from '../Services/sceneAudio';

interface EditorAudioPanelProps {
    audio: AudioConfig;
    assets: EditorAsset[];
    /** The template leaves sound out of the published invitation, so it can only be tried while designing. */
    locked: boolean;
    /** The full preview is open and plays the invitation's own sound, so listening here pauses. */
    previewOpen: boolean;
    onChange: (audio: AudioConfig) => void;
}

function PlaybackIcon({ playing }: { playing: boolean }) {
    return <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false">
        <path d={playing ? 'M6 5h4v14H6zM14 5h4v14h-4z' : 'M8 5v14l11-7z'} />
    </svg>;
}

export function EditorAudioPanel({ audio, assets, locked, previewOpen, onChange }: EditorAudioPanelProps) {
    const tracks = audioTracks(assets);
    const selected = selectedTrackId(audio);
    const sampler = useTrackSampler(audio.volume, previewOpen);
    const failedTrack = tracks.find((track) => track.id === sampler.failedId);
    const choose = (track: EditorAsset | null): void => {
        const next = chooseTrack(audio, track);
        if (next !== audio) onChange(next);
    };

    return <section className="editor-panel-section editor-audio-panel" aria-labelledby="audio-title">
        <header><span>06</span><div><h2 id="audio-title">صوت الدعوة</h2><p>استمعي إلى المقاطع ثم اختاري ما يرافق دعوتك.</p></div></header>
        {locked && <p className="editor-audio-note" role="note">هذا القالب لا يشمل الصوت. تسمعينه أثناء التصميم وفي المعاينة، لكنه لا يظهر في الدعوة المنشورة.</p>}
        {tracks.length === 0 ? <p className="editor-empty">لا توجد مقاطع صوتية في المكتبة حالياً.</p> : <fieldset className="editor-audio-choices">
            <legend>المقاطع المتاحة</legend>
            <div className="editor-audio-row is-none">
                <label><input type="radio" name="invitation-audio" checked={selected === null} onChange={() => choose(null)} /><span>بدون صوت</span></label>
            </div>
            {tracks.map((track) => {
                const playing = sampler.playingId === track.id;
                return <div key={track.id} className={selected === track.id ? 'editor-audio-row is-selected' : 'editor-audio-row'}>
                    <label><input type="radio" name="invitation-audio" checked={selected === track.id} onChange={() => choose(track)} /><span>{track.name}</span></label>
                    <button type="button" className={playing ? 'editor-audio-play is-playing' : 'editor-audio-play'} aria-label={`${playing ? 'إيقاف الاستماع إلى' : 'استماع إلى'} ${track.name}`} onClick={() => sampler.toggle(track)}><PlaybackIcon playing={playing} /></button>
                </div>;
            })}
        </fieldset>}
        {selected !== null && <div className="editor-control-stack">
            <label>مستوى الصوت · {Math.round(audio.volume * 100)}%<input type="range" min={0} max={1} step={0.05} value={audio.volume} onChange={(event) => onChange({ ...audio, volume: Number(event.target.value) })} /></label>
        </div>}
        <p className="editor-audio-hint">يبدأ الصوت عندما يفتح الضيف الدعوة، ويجد زراً لإيقافه. جرّبيه كما يسمعه الضيف من زر «معاينة الدعوة» في الأعلى.</p>
        <p className="editor-audio-status" role="status">{failedTrack ? `تعذّر تشغيل «${failedTrack.name}».` : ''}</p>
    </section>;
}
