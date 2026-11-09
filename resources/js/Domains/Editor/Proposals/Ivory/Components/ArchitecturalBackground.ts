import type { ProposalDrawing } from '../Services/ProposalDrawing';
export async function ArchitecturalBackground(drawing: ProposalDrawing): Promise<number> {
    if (!drawing.visible('architecture')) return 0;
    await drawing.image('architecture', 450, 0, true);
    return drawing.part('architecture').height * drawing.part('architecture').scale;
}
