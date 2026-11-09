import { FabricImage, Group, Rect } from 'fabric';
import { loadProposalImage } from '../Services/ProposalDrawing';
import type { ProposalDrawing } from '../Services/ProposalDrawing';
export async function InvitationPaper(drawing: ProposalDrawing, top: number, height: number): Promise<void> {
    if (!drawing.visible('paper')) return;
    const part = drawing.part('paper');
    const base = new Rect({ width: part.width, height, fill: part.color });
    const texture = new FabricImage(await loadProposalImage('/editor/materials/v1/cotton.svg'));
    texture.set({ scaleX: part.width / texture.width, scaleY: height / texture.height, opacity: .22 });
    drawing.add('paper', new Group([base, texture]), 450 - part.width / 2, top, part.width, height);
}
