import type { DesignProposal } from '../../../../../Types/DesignProposal';
import { MAXIMUM_IMAGE_BYTES, MAXIMUM_PROPOSAL_BYTES, parseProposal } from './proposalDocument';

export function downloadProposalFile(content: string, name: string, mime: string): void {
    const link = document.createElement('a');
    const url = URL.createObjectURL(new Blob([content], { type: mime }));
    link.href = url; link.download = name; link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}
export async function readProposalFile(file: File): Promise<DesignProposal> {
    if (file.size > MAXIMUM_PROPOSAL_BYTES) throw new Error('ملف المسودة أكبر من الحجم المسموح.');
    return parseProposal(JSON.parse(await file.text()) as unknown);
}
export async function readProposalImage(file: File): Promise<string> {
    if (file.size > MAXIMUM_IMAGE_BYTES || !['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) throw new Error('اختاري PNG أو JPG أو WebP حتى 1 ميغابايت.');
    const data = await new Promise<string>((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => typeof reader.result === 'string' ? resolve(reader.result) : reject(new Error('تعذّرت قراءة الصورة.'));
        reader.onerror = () => reject(new Error('تعذّرت قراءة الصورة.'));
        reader.readAsDataURL(file);
    });
    const image = new Image();
    image.src = data;
    await image.decode();
    if (image.width > 4096 || image.height > 4096) throw new Error('الحد الأقصى لأبعاد الصورة 4096 بكسل.');
    return data;
}
