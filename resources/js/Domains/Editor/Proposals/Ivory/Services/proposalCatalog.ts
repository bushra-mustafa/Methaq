import type { ProposalFont, ProposalPartId } from '../../../../../Types/DesignProposal';
export const PROPOSAL_WIDTH = 900;
export const PROPOSAL_ASSET_ROOT = '/editor/proposals/ivory-wedding/v1/';
export const PROPOSAL_LABELS: Record<ProposalPartId, string> = {
    "architecture": "القوس المعماري",
    "paper": "ورقة الدعوة",
    "ornament": "زخرفة الورقة",
    "envelope": "الظرف",
    "envelope-shadow": "ظل الظرف",
    "envelope-body": "جسم الظرف",
    "envelope-flap": "الغطاء المتموّج",
    "envelope-badge": "شارة البجعتين",
    "title": "عنوان الدعوة",
    "names": "أسماء أصحاب المناسبة",
    "message": "رسالة الدعوة",
    "message-ar": "الرسالة العربية",
    "message-en": "الرسالة الإنجليزية",
    "venue": "بيانات المكان",
    "venue-icon": "أيقونة الثريّا",
    "venue-name": "اسم القاعة",
    "venue-address": "العنوان",
    "schedule": "التاريخ والوقت",
    "schedule-icon": "أيقونة الساعة",
    "date": "التاريخ",
    "day": "اليوم",
    "time": "الوقت كتابةً",
    "closing": "الختام والتوقيع",
    "closing-text": "العبارة الختامية",
    "signature": "التوقيع",
    "swans": "البجعتان"
};
export const PROPOSAL_PARENTS: Partial<Record<ProposalPartId, ProposalPartId>> = {
    "envelope-shadow": "envelope",
    "envelope-body": "envelope",
    "envelope-flap": "envelope",
    "envelope-badge": "envelope",
    "message-ar": "message",
    "message-en": "message",
    "venue-icon": "venue",
    "venue-name": "venue",
    "venue-address": "venue",
    "schedule-icon": "schedule",
    "date": "schedule",
    "day": "schedule",
    "time": "schedule",
    "closing-text": "closing",
    "signature": "closing"
};
export const PROPOSAL_FONT_FAMILIES: Record<ProposalFont, string> = { amiri: 'Ivory Amiri', pinyon: 'Ivory Pinyon', georgia: 'Georgia', tajawal: 'Ivory Tajawal' };
export const PROPOSAL_FONT_LABELS: Record<ProposalFont, string> = { amiri: 'أميري', pinyon: 'Pinyon Script', georgia: 'Georgia', tajawal: 'تجوال' };
export const PROPOSAL_ASSETS = ['architecture', 'ornament', 'swans', 'chandelier', 'clock'] as const;
export function descendants(id: ProposalPartId): ProposalPartId[] {
    const children = (Object.keys(PROPOSAL_PARENTS) as ProposalPartId[]).filter((key) => PROPOSAL_PARENTS[key] === id);
    return [id, ...children.flatMap(descendants)];
}
