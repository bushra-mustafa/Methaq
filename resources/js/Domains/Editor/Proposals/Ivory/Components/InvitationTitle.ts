import type { ProposalDrawing } from '../Services/ProposalDrawing';
export function InvitationTitle(drawing: ProposalDrawing, top: number): number { return drawing.text('title', 450, top); }
