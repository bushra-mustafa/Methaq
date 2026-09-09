import type { InvitationCover } from '../../../Types/SceneConfig';
import type { Palette } from '../../../Types/Palette';
import { floralImage } from '../Services/referenceArtwork';
export function InvitationCoverArt({ decoration, palette }: { decoration: InvitationCover['decoration']; palette: Palette }) {
    if (decoration === 'none') return null;
    return <div className="invitation-cover-art" aria-hidden="true"><div className="invitation-envelope-halo" />{decoration === 'floral' && <>
        <img className="is-left" src={floralImage(palette.values.effect, palette.values.accent)} alt="" />
        <img className="is-right" src={floralImage(palette.values.effect, palette.values.accent)} alt="" />
    </>}</div>;
}
