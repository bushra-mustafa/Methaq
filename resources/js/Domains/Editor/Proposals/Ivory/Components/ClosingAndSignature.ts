import type { ProposalDrawing } from '../Services/ProposalDrawing';
export function ClosingAndSignature(drawing: ProposalDrawing, top: number): number {
    if (!drawing.visible('closing')) return 0;
    drawing.anchor('closing', 450, top);
    const text = drawing.text('closing-text', 450, top);
    const signature = drawing.text('signature', 450, top + text);
    return (text + signature) * drawing.part('closing').scale + (text + signature > 0 ? drawing.part('closing').gap : 0);
}
