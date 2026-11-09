import type { ProposalDrawing } from '../Services/ProposalDrawing';
export function CoupleNames(drawing: ProposalDrawing, top: number): number { return drawing.text('names', 450, top); }
