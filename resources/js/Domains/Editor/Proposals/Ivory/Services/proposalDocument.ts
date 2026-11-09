import defaultDocument from '../../../../../../design-proposals/ivory-wedding.json' with { type: 'json' };
import { PROPOSAL_FONTS, PROPOSAL_PART_IDS } from '../../../../../Types/DesignProposal.ts';
import type { DesignProposal, ProposalPart, ProposalPartId, ProposalSnapshot } from '../../../../../Types/DesignProposal';
import { descendants, PROPOSAL_ASSETS, PROPOSAL_PARENTS } from './proposalCatalog.ts';

export const MAXIMUM_PROPOSAL_BYTES = 6 * 1024 * 1024;
export const MAXIMUM_IMAGE_BYTES = 1024 * 1024;

function record(value: unknown): Record<string, unknown> {
    if (!value || typeof value !== 'object' || Array.isArray(value)) throw new Error('بيانات الاقتراح غير صالحة.');
    return value as Record<string, unknown>;
}
function number(value: unknown, min: number, max: number): number {
    if (typeof value !== 'number' || !Number.isFinite(value) || value < min || value > max) throw new Error('إحدى القيم خارج الحدود المسموحة.');
    return value;
}
function choice<T extends string>(value: unknown, values: readonly T[]): T {
    if (typeof value !== 'string' || !values.includes(value as T)) throw new Error('خيار غير مسموح.');
    return value as T;
}
export function validImageSource(source: unknown): string {
    if (typeof source !== 'string') throw new Error('الصورة غير صالحة.');
    if ((PROPOSAL_ASSETS as readonly string[]).includes(source)) return source;
    if (source.length > Math.ceil(MAXIMUM_IMAGE_BYTES * 4 / 3) + 64 || !/^data:image\/(png|jpeg|webp);base64,[A-Za-z0-9+/]+={0,2}$/.test(source)) throw new Error('استخدمي صورة PNG أو JPG أو WebP، بحجم لا يتجاوز 1 ميغابايت.');
    return source;
}
export function parseProposal(value: unknown): DesignProposal {
    const doc = record(value);
    if (doc.schemaVersion !== 1 || doc.template !== 'ivory-wedding' || JSON.stringify(doc).length > MAXIMUM_PROPOSAL_BYTES) throw new Error('إصدار الاقتراح أو حجمه غير مدعوم.');
    if (!Array.isArray(doc.parts) || doc.parts.length !== PROPOSAL_PART_IDS.length) throw new Error('عناصر الاقتراح غير مكتملة.');
    const ids = new Set<string>();
    const parts = doc.parts.map((raw): ProposalPart => {
        const part = record(raw);
        const id = choice(part.id, PROPOSAL_PART_IDS);
        const expected = defaultDocument.parts.find((item) => item.id === id);
        if (ids.has(id) || part.kind !== expected?.kind || typeof part.visible !== 'boolean') throw new Error('نوع العنصر أو معرّفه غير صالح.');
        ids.add(id);
        const color = typeof part.color === 'string' && /^#[0-9a-f]{6}$/i.test(part.color) ? part.color : null;
        if (!color) throw new Error('اللون غير صالح.');
        const common = { id, visible: part.visible, color, x: number(part.x, -1800, 1800), y: number(part.y, -5000, 5000), width: number(part.width, 20, 1200), height: number(part.height, 10, 1500), scale: number(part.scale, .2, 3), rotation: number(part.rotation, -180, 180), opacity: number(part.opacity, 0, 1), gap: number(part.gap, 0, 200) };
        if (part.kind === 'text') {
            if (typeof part.content !== 'string' || part.content.length > 2000 || /[<>]/.test(part.content)) throw new Error('النص يجب أن يكون عادياً، حتى 2000 حرف.');
            return { ...common, kind: 'text', content: part.content, font: choice(part.font, PROPOSAL_FONTS), fontSize: number(part.fontSize, 14, 120), alignment: choice(part.alignment, ['left', 'center', 'right']), direction: choice(part.direction, ['rtl', 'ltr', 'auto']) };
        }
        if (part.kind === 'image') return { ...common, kind: 'image', source: validImageSource(part.source) };
        return { ...common, kind: choice(part.kind, ['shape', 'group']) };
    });
    const leaves = parts.filter((part) => part.kind !== 'group').map((part) => part.id);
    if (!Array.isArray(doc.layerOrder) || doc.layerOrder.length !== leaves.length) throw new Error('ترتيب الطبقات غير صالح.');
    const layerOrder = doc.layerOrder.map((id) => choice(id, leaves));
    if (new Set(layerOrder).size !== leaves.length) throw new Error('تكرار في ترتيب الطبقات.');
    return { schemaVersion: 1, template: 'ivory-wedding', parts, layerOrder };
}
export function createIvoryProposal(): DesignProposal { return parseProposal(defaultDocument); }
export function parseProposalSnapshot(value: unknown): ProposalSnapshot {
    const snapshot = record(value);
    return { document: snapshot.document === null ? null : parseProposal(snapshot.document), revision: number(snapshot.revision, 0, 4294967295) };
}
export function updateProposalPart(document: DesignProposal, id: ProposalPartId, update: (part: ProposalPart) => ProposalPart): DesignProposal {
    return { ...document, parts: document.parts.map((part) => part.id === id ? update(part) : part) };
}
export function moveProposalLayer(document: DesignProposal, id: ProposalPartId, direction: -1 | 1): DesignProposal {
    const members = new Set(descendants(id));
    const selected = document.layerOrder.filter((part) => members.has(part));
    const remaining = document.layerOrder.filter((part) => !members.has(part));
    if (!selected.length) return document;
    const first = Math.min(...selected.map((part) => document.layerOrder.indexOf(part)));
    const last = Math.max(...selected.map((part) => document.layerOrder.indexOf(part)));
    const neighbor = document.layerOrder[direction === 1 ? last + 1 : first - 1];
    if (!neighbor) return document;
    const insertion = remaining.indexOf(neighbor) + (direction === 1 ? 1 : 0);
    remaining.splice(insertion, 0, ...selected);
    return { ...document, layerOrder: remaining };
}
export function localMovement(document: DesignProposal, id: ProposalPartId, dx: number, dy: number): { x: number; y: number } {
    let parent = PROPOSAL_PARENTS[id];
    let scale = 1;
    let angle = 0;
    while (parent) {
        const group = document.parts.find((part) => part.id === parent)!;
        scale *= group.scale;
        angle += group.rotation;
        parent = PROPOSAL_PARENTS[parent];
    }
    const rad = -angle * Math.PI / 180;
    return { x: (dx * Math.cos(rad) - dy * Math.sin(rad)) / scale, y: (dx * Math.sin(rad) + dy * Math.cos(rad)) / scale };
}
