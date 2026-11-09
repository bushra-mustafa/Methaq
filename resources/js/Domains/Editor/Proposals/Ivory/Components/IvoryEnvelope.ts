import { Ellipse, Group, Line, Path, Rect, Shadow } from 'fabric';
import type { ProposalImage } from '../../../../../Types/DesignProposal';
import type { ProposalDrawing } from '../Services/ProposalDrawing';
import { FLAP_PATH, HEART_FRAME_PATH } from '../Services/proposalArtwork';

export function EnvelopeShadow(drawing: ProposalDrawing, top: number): void {
    const part = drawing.part('envelope-shadow');
    const shadow = new Ellipse({ rx: 245, ry: 12, fill: part.color, shadow: new Shadow({ color: part.color, blur: 18, offsetY: 8 }) });
    drawing.add(part.id, shadow, 450 - part.width / 2, top + 323, part.width, part.height);
}
export function EnvelopeBody(drawing: ProposalDrawing, top: number): void {
    const part = drawing.part('envelope-body');
    const paper = new Rect({ width: 490, height: 320, fill: part.color, stroke: '#E0D7C9', strokeWidth: 1 });
    const crease = new Path('M0 0 L245 172 L490 0 M0 320 L200 146 M490 320 L290 146', { fill: '', stroke: '#DFD6C8', strokeWidth: 1.3 });
    const lowerHighlight = new Line([0, 319, 490, 319], { stroke: '#ffffff', strokeWidth: 2 });
    drawing.add(part.id, new Group([paper, crease, lowerHighlight]), 450 - part.width / 2, top, part.width, part.height);
}
export function EnvelopeFlap(drawing: ProposalDrawing, top: number): void {
    const part = drawing.part('envelope-flap');
    const fill = new Path(FLAP_PATH, { fill: part.color, stroke: '#E4DCCE', strokeWidth: 1, shadow: new Shadow({ color: '#A49A8633', blur: 7, offsetY: 5 }) });
    const edge = new Path(FLAP_PATH, { left: 10, top: 5, scaleX: .96, scaleY: .95, fill: '', stroke: '#D8CFC0', strokeWidth: 1.1 });
    const inner = new Path(FLAP_PATH, { left: 17, top: 9, scaleX: .93, scaleY: .91, fill: '', stroke: '#FFFFFF', strokeWidth: 2 });
    drawing.add(part.id, new Group([fill, edge, inner]), 450 - part.width / 2, top, part.width, part.height);
}
export async function EnvelopeBadge(drawing: ProposalDrawing, top: number): Promise<void> {
    if (!drawing.visible('envelope-badge')) return;
    const part = drawing.part('envelope-badge') as ProposalImage;
    const frame = new Path(HEART_FRAME_PATH, { fill: '#F8F5EE', stroke: '#D8CFC0', strokeWidth: 1.2 });
    const relief = await drawing.imageObject(part);
    const scale = 85 / relief.width;
    relief.set({ scaleX: scale, scaleY: scale, left: 18, top: 30 });
    drawing.add(part.id, new Group([frame, relief]), 450 - part.width / 2, top + 84, part.width, part.height);
}
export async function IvoryEnvelope(drawing: ProposalDrawing, top: number): Promise<number> {
    if (!drawing.visible('envelope')) return 0;
    drawing.anchor('envelope', 450, top);
    EnvelopeShadow(drawing, top);
    EnvelopeBody(drawing, top);
    EnvelopeFlap(drawing, top);
    await EnvelopeBadge(drawing, top);
    return 340 * drawing.part('envelope').scale + drawing.part('envelope').gap;
}
