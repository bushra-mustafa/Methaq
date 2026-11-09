import type { ProposalDrawing } from '../Services/ProposalDrawing';
export function InvitationMessage(drawing: ProposalDrawing, top: number): number {
    if (!drawing.visible('message')) return 0;
    drawing.anchor('message', 450, top);
    const arabic = drawing.text('message-ar', 450, top);
    const english = drawing.text('message-en', 450, top + arabic);
    return (arabic + english) * drawing.part('message').scale + (arabic + english > 0 ? drawing.part('message').gap : 0);
}
