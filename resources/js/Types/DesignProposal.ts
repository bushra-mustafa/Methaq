export const PROPOSAL_PART_IDS = ["architecture","paper","ornament","envelope","envelope-shadow","envelope-body","envelope-flap","envelope-badge","title","names","message","message-ar","message-en","venue","venue-icon","venue-name","venue-address","schedule","schedule-icon","date","day","time","closing","closing-text","signature","swans"] as const;
export type ProposalPartId = typeof PROPOSAL_PART_IDS[number];
export const PROPOSAL_FONTS = ['amiri', 'pinyon', 'georgia', 'tajawal'] as const;
export type ProposalFont = typeof PROPOSAL_FONTS[number];

export interface ProposalFrame {
    id: ProposalPartId;
    visible: boolean;
    x: number;
    y: number;
    width: number;
    height: number;
    scale: number;
    rotation: number;
    opacity: number;
    gap: number;
    color: string;
}
export interface ProposalText extends ProposalFrame {
    kind: 'text';
    content: string;
    font: ProposalFont;
    fontSize: number;
    alignment: 'left' | 'center' | 'right';
    direction: 'rtl' | 'ltr' | 'auto';
}
export interface ProposalImage extends ProposalFrame { kind: 'image'; source: string }
export interface ProposalShape extends ProposalFrame { kind: 'shape' }
export interface ProposalGroup extends ProposalFrame { kind: 'group' }
export type ProposalPart = ProposalText | ProposalImage | ProposalShape | ProposalGroup;
export interface DesignProposal {
    schemaVersion: 1;
    template: 'ivory-wedding';
    parts: ProposalPart[];
    layerOrder: ProposalPartId[];
}
export interface ProposalSnapshot { document: DesignProposal | null; revision: number }
