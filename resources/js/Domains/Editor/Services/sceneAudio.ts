import type { DesignDocument } from '../../../Types/DesignDocument';
import type { EditorAsset } from '../../../Types/Editor';
import type { AudioConfig } from '../../../Types/SceneConfig';

/** The sound tracks administrators have put in the library. */
export function audioTracks(assets: EditorAsset[]): EditorAsset[] {
    return assets.filter((asset) => asset.type === 'audio');
}

/** The track the invitation plays, or null while its sound is off. */
export function selectedTrackId(audio: AudioConfig): string | null {
    return audio.enabled ? audio.asset?.assetId ?? null : null;
}

/**
 * Picking a track switches the sound on and picking none switches it off; the volume the owner set stays either way.
 * Returns the very same object when nothing changes, so a caller can skip a pointless history step.
 */
export function chooseTrack(audio: AudioConfig, track: EditorAsset | null): AudioConfig {
    if (track === null) return audio.enabled || audio.asset !== null ? { ...audio, enabled: false, asset: null } : audio;
    const alreadyChosen = audio.enabled && audio.asset?.assetId === track.id && audio.asset.version === track.version;
    return alreadyChosen ? audio : { ...audio, enabled: true, asset: { assetId: track.id, version: track.version } };
}

export function withAudio(document: DesignDocument, audio: AudioConfig): DesignDocument {
    return { ...document, scene: { ...document.scene, audio } };
}
