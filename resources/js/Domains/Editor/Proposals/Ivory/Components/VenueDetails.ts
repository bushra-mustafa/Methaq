import type { ProposalDrawing } from '../Services/ProposalDrawing';
export async function VenueDetails(drawing: ProposalDrawing, top: number, center: number): Promise<number> {
    if (!drawing.visible('venue')) return 0;
    drawing.anchor('venue', center, top);
    let height = await drawing.image('venue-icon', center, top);
    height += drawing.text('venue-name', center, top + height);
    height += drawing.text('venue-address', center, top + height);
    return height * drawing.part('venue').scale + drawing.part('venue').gap;
}
