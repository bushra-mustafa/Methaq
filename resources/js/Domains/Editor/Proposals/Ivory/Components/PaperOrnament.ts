import type { ProposalDrawing } from '../Services/ProposalDrawing';
export async function PaperOrnament(drawing: ProposalDrawing, top: number): Promise<void> {
    await drawing.image('ornament', 450, top);
}
