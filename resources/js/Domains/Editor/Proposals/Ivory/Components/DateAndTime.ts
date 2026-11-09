import type { ProposalDrawing } from '../Services/ProposalDrawing';
export async function DateAndTime(drawing: ProposalDrawing, top: number, center: number): Promise<number> {
    if (!drawing.visible('schedule')) return 0;
    drawing.anchor('schedule', center, top);
    let height = await drawing.image('schedule-icon', center, top);
    height += drawing.text('date', center, top + height);
    height += drawing.text('day', center, top + height);
    height += drawing.text('time', center, top + height);
    return height * drawing.part('schedule').scale + drawing.part('schedule').gap;
}
