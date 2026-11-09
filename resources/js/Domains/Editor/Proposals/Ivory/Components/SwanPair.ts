import type { ProposalDrawing } from '../Services/ProposalDrawing';
export async function SwanPair(drawing: ProposalDrawing, top: number): Promise<number> { return drawing.image('swans', 450, top); }
