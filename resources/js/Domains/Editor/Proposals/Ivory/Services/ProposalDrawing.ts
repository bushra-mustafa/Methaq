import { Circle, FabricImage, Group, Line, Path, Textbox } from 'fabric';
import type { FabricObject } from 'fabric';
import type { DesignProposal, ProposalImage, ProposalPart, ProposalPartId, ProposalText } from '../../../../../Types/DesignProposal';
import { PROPOSAL_ASSET_ROOT, PROPOSAL_FONT_FAMILIES, PROPOSAL_PARENTS } from './proposalCatalog';
import { CHANDELIER_PATH } from './proposalArtwork';

export interface DrawnPart { id: ProposalPartId; object: FabricObject }
export interface Point { x: number; y: number }
const loadedImages = new Map<string, Promise<HTMLImageElement>>();
let fontLoading: Promise<unknown> | null = null;

export async function loadProposalFonts(): Promise<void> {
    fontLoading ??= Promise.all([
        ['Ivory Amiri', '/brand/fonts/Amiri-Regular.ttf'],
        ['Ivory Pinyon', '/brand/fonts/PinyonScript-Regular.ttf'],
        ['Ivory Tajawal', '/brand/fonts/Tajawal-Regular.ttf'],
    ].map(async ([family, url]) => {
        const face = await new FontFace(family!, 'url("' + url + '")').load();
        document.fonts.add(face);
    })).catch((error: unknown) => { fontLoading = null; throw error; });
    await fontLoading;
}
export function loadProposalImage(source: string): Promise<HTMLImageElement> {
    const existing = loadedImages.get(source);
    if (existing) return existing;
    const promise = new Promise<HTMLImageElement>((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = () => { loadedImages.delete(source); reject(new Error('تعذّر تحميل صورة. أعيدي المحاولة أو استبدليها.')); };
        image.src = source;
    });
    if (loadedImages.size >= 16) loadedImages.delete(loadedImages.keys().next().value!);
    loadedImages.set(source, promise);
    return promise;
}

export class ProposalDrawing {
    readonly objects: DrawnPart[] = [];
    readonly anchors = new Map<ProposalPartId, Point>();
    private readonly parts: Map<ProposalPartId, ProposalPart>;
    constructor(readonly document: DesignProposal) { this.parts = new Map(document.parts.map((part) => [part.id, part])); }
    part(id: ProposalPartId): ProposalPart { return this.parts.get(id)!; }
    visible(id: ProposalPartId): boolean {
        const part = this.part(id);
        const parent = PROPOSAL_PARENTS[id];
        return part.visible && (part.kind !== 'text' || part.content.trim().length > 0) && (!parent || this.visible(parent));
    }
    anchor(id: ProposalPartId, x: number, y: number): void { this.anchors.set(id, { x, y }); }
    add(id: ProposalPartId, object: FabricObject, left: number, top: number, width: number, height: number): void {
        if (!this.visible(id)) { object.dispose(); return; }
        const part = this.part(id);
        let x = left + width / 2 + part.x;
        let y = top + height / 2 + part.y;
        let scale = part.scale;
        let angle = part.rotation;
        let opacity = part.opacity;
        let parent = PROPOSAL_PARENTS[id];
        while (parent) {
            const group = this.part(parent);
            const anchor = this.anchors.get(parent) ?? { x: 0, y: 0 };
            const rad = group.rotation * Math.PI / 180;
            const dx = (x - anchor.x) * group.scale;
            const dy = (y - anchor.y) * group.scale;
            x = anchor.x + group.x + dx * Math.cos(rad) - dy * Math.sin(rad);
            y = anchor.y + group.y + dx * Math.sin(rad) + dy * Math.cos(rad);
            scale *= group.scale;
            angle += group.rotation;
            opacity *= group.opacity;
            parent = PROPOSAL_PARENTS[parent];
        }
        object.set({ originX: 'center', originY: 'center', left: x, top: y, scaleX: width / object.width * scale, scaleY: height / object.height * scale, angle, opacity, hasControls: false, borderColor: '#074b36', padding: 5, objectCaching: false });
        object.setCoords();
        this.objects.push({ id, object });
    }
    text(id: ProposalPartId, center: number, top: number): number {
        if (!this.visible(id)) return 0;
        const part = this.part(id) as ProposalText;
        const direction = part.direction === 'auto' ? /[\u0600-\u06ff]/.test(part.content) ? 'rtl' : 'ltr' : part.direction;
        const text = new Textbox(part.content, { width: part.width, fontFamily: PROPOSAL_FONT_FAMILIES[part.font], fontSize: part.fontSize, lineHeight: direction === 'rtl' ? 1.5 : 1.35, textAlign: part.alignment, direction, fill: part.color, splitByGrapheme: false, editable: false });
        if (text.width > part.width) { text.set({ splitByGrapheme: true, width: part.width }); text.initDimensions(); }
        const height = text.height;
        this.add(id, text, center - part.width / 2, top, part.width, height);
        return height * part.scale + part.gap;
    }
    async imageObject(part: ProposalImage): Promise<FabricObject> {
        if (part.source === 'chandelier') return new Path(CHANDELIER_PATH, { fill: '', stroke: part.color, strokeWidth: 1.4, strokeLineCap: 'round', strokeLineJoin: 'round' });
        if (part.source === 'clock') {
            const ticks = Array.from({ length: 12 }, (_, i) => {
                const rad = i * Math.PI / 6;
                return new Line([50 + Math.sin(rad) * 34, 50 - Math.cos(rad) * 34, 50 + Math.sin(rad) * 40, 50 - Math.cos(rad) * 40], { stroke: part.color, strokeWidth: 1.4 });
            });
            return new Group([new Circle({ radius: 46, left: 4, top: 4, fill: '', stroke: part.color, strokeWidth: 1.5 }), new Circle({ radius: 41, left: 9, top: 9, fill: '', stroke: part.color, strokeWidth: .8 }), ...ticks, new Path('M50 23 V50 L66 59', { fill: '', stroke: part.color, strokeWidth: 1.8, strokeLineCap: 'round' }), new Circle({ radius: 2, left: 48, top: 48, fill: part.color })]);
        }
        const source = part.source.startsWith('data:') ? part.source : PROPOSAL_ASSET_ROOT + part.source + '.png';
        return new FabricImage(await loadProposalImage(source));
    }
    async image(id: ProposalPartId, center: number, top: number, cover = false): Promise<number> {
        if (!this.visible(id)) return 0;
        const part = this.part(id) as ProposalImage;
        const object = await this.imageObject(part);
        let width = part.width;
        let height = part.height;
        if (cover && object instanceof FabricImage) {
            const factor = Math.max(width / object.width, height / object.height);
            const cropWidth = width / factor, cropHeight = height / factor;
            object.set({ cropX: (object.width - cropWidth) / 2, cropY: (object.height - cropHeight) / 2, width: cropWidth, height: cropHeight });
        } else {
            const ratio = Math.min(width / object.width, height / object.height);
            width = object.width * ratio; height = object.height * ratio;
        }
        this.add(id, object, center - width / 2, top, width, height);
        return height * part.scale + part.gap;
    }
}
