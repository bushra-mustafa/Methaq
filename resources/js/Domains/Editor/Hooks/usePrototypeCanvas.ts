import { useEffect, useRef, useState } from 'react';
import { Canvas, Group, Textbox, FabricText, loadSVGFromString, util } from 'fabric';
import type { FabricObject } from 'fabric';
import type { PrototypeDesign, TextSlot, TextPosition } from '../../../Types/PrototypeDesign';
import { cardArtwork } from '../Services/referenceArtwork';

function nameLines(design: PrototypeDesign): string {
    return design.language === 'en' ? design.englishNames.replace(/\s*&\s*/, '\n&\n') : design.arabicNames.replace(/\s+و\s*/, '\n\u200cو\u200c\n');
}
function updateLabel(label: Textbox, slot: TextSlot, design: PrototypeDesign): void {
    label.set({ left: design.positions[slot].x, top: design.positions[slot].y, fill: slot === 'blessing' ? design.accent : design.ink });
    if (slot === 'names') label.set({ text: nameLines(design), fontFamily: design.language === 'ar' ? 'Invitation Amiri' : 'Invitation Pinyon', direction: design.language === 'ar' ? 'rtl' : 'ltr', fontSize: design.language === 'ar' ? 145 : 174, lineHeight: 0.88 });
    if (slot === 'blessing') label.set({ text: design.blessing, fontSize: 58 });
    if (slot === 'date') label.set({ text: design.dateLabel, fontSize: 45 });
    const maximumHeight = slot === 'names' ? 500 : slot === 'blessing' ? 215 : 170;
    while (label.height > maximumHeight && label.fontSize > 24) label.set({ fontSize: label.fontSize - 2 });
    label.setCoords();
}
function artworkKey(design: PrototypeDesign): string {
    return [design.background, design.paper, design.accent, design.envelope, design.florals].join(':');
}
async function createArtwork(design: PrototypeDesign): Promise<FabricObject> {
    const svg = await loadSVGFromString(cardArtwork(design));
    const group = util.groupSVGElements(svg.objects.filter((object) => object !== null), svg.options);
    group.set({ selectable: false, evented: false });
    return group;
}
export function usePrototypeCanvas(design: PrototypeDesign, onMove?: (slot: TextSlot, position: TextPosition) => void) {
    const host = useRef<HTMLDivElement>(null);
    const instance = useRef<Canvas | null>(null);
    const artwork = useRef<FabricObject | null>(null);
    const renderedArtwork = useRef('');
    const latest = useRef(design);
    const move = useRef(onMove);
    const [ready, setReady] = useState(false);
    const [error, setError] = useState('');
    const labels = useRef<Partial<Record<TextSlot, Textbox>>>({});
    latest.current = design;
    move.current = onMove;

    useEffect(() => {
        const container = host.current;
        if (!container) return;
        let cancelled = false;
        let canvas: Canvas | null = null;
        const element = document.createElement('canvas');
        element.setAttribute('aria-label', 'معاينة البطاقة؛ يمكن تعديل النصوص من لوحة الخيارات');
        container.appendChild(element);
        async function initialize() {
            try {
                await Promise.all([document.fonts.load('24px "Invitation Amiri"'), document.fonts.load('24px "Invitation Tajawal"'), document.fonts.load('24px "Invitation Pinyon"')]);
                if (cancelled) return;
                const d = latest.current;
                const decoration = await createArtwork(d);
                if (cancelled) return;
                canvas = new Canvas(element, { width: 1080, height: 1920, enableRetinaScaling: false, selection: false });
                canvas.setDimensions({ width: 360, height: 640 }, { cssOnly: true });
                instance.current = canvas;
                artwork.current = decoration;
                renderedArtwork.current = artworkKey(d);
                canvas.add(decoration);
                const specs: Array<[TextSlot, string, string, number, CanvasDirection]> = [
                    ['intro', 'بكل حب، ندعوكم لمشاركتنا فرحتنا', 'Invitation Tajawal', 33, 'rtl'],
                    ['names', nameLines(d), 'Invitation Pinyon', 174, 'ltr'],
                    ['blessing', d.blessing, 'Invitation Amiri', 58, 'rtl'],
                    ['date', d.dateLabel, 'Invitation Tajawal', 45, 'rtl'],
                ];
                const currentLabels: Partial<Record<TextSlot, Textbox>> = {};
                for (const [slot, text, fontFamily, fontSize, direction] of specs) {
                    const label = new Textbox(text, { width: 840, objectCaching: false, fontFamily, fontSize, textAlign: 'center', direction, lineHeight: 1.4, selectable: Boolean(move.current), evented: Boolean(move.current), hasControls: false, lockScalingX: true, lockScalingY: true, lockRotation: true, editable: false });
                    updateLabel(label, slot, d);
                    label.on('modified', () => {
                        const position = { x: Math.max(0, Math.min(240, label.left)), y: Math.max(0, Math.min(1600, label.top)) };
                        label.set({ left: position.x, top: position.y });
                        label.setCoords();
                        move.current?.(slot, position);
                    });
                    currentLabels[slot] = label;
                    canvas.add(label);
                }
                labels.current = currentLabels;
                canvas.add(new Textbox('بحضوركم تكتمل فرحتنا', { objectCaching: false, left: 190, top: 1690, width: 700, textAlign: 'center', direction: 'rtl', fontFamily: 'Invitation Amiri', fontSize: 50, fill: d.ink, selectable: false, evented: false }));
                canvas.requestRenderAll();
                setReady(true);
            } catch {
                if (!cancelled) setError('تعذّر تحميل الخطوط أو مساحة الرسم. حدّث الصفحة للمحاولة مجدداً.');
            }
        }
        void initialize();
        return () => {
            cancelled = true;
            instance.current = null;
            labels.current = {};
            artwork.current = null;
            if (canvas) void canvas.dispose().catch(() => undefined);
            container.replaceChildren();
        };
    }, []);

    useEffect(() => {
        const canvas = instance.current;
        if (!canvas || !ready) return;
        for (const slot of ['intro', 'names', 'blessing', 'date'] as const) {
            const label = labels.current[slot];
            if (label) updateLabel(label, slot, design);
        }
        canvas.getObjects().forEach((object) => {
            if (object instanceof FabricText && !Object.values(labels.current).some((label) => label === object)) object.set({ fill: design.ink });
        });
        canvas.requestRenderAll();
    }, [design, ready]);

    useEffect(() => {
        const canvas = instance.current;
        if (!canvas || !ready) return;
        let cancelled = false;
        setError('');
        const pendingDesign = latest.current;
        void createArtwork(pendingDesign).then((decoration) => {
            if (cancelled) return;
            if (artwork.current) canvas.remove(artwork.current);
            artwork.current = decoration;
            renderedArtwork.current = artworkKey(pendingDesign);
            canvas.insertAt(0, decoration);
            canvas.requestRenderAll();
        }).catch(() => { if (!cancelled) setError('تعذّر تحديث الزخارف. أعد اختيار اللون.'); });
        return () => { cancelled = true; };
    }, [design.background, design.paper, design.accent, design.envelope, design.florals, ready]);

    function exportPreview(quality: 'preview' | '4k' = 'preview'): string {
        const canvas = instance.current;
        if (!canvas || !ready || error || renderedArtwork.current !== artworkKey(latest.current)) throw new Error('المعاينة غير جاهزة.');
        canvas.discardActiveObject();
        const cacheSettings = new Map<FabricObject, boolean>();
        function disableCache(object: FabricObject): void {
            cacheSettings.set(object, object.objectCaching);
            object.set({ objectCaching: false });
            if (object instanceof Group) object.getObjects().forEach(disableCache);
        }
        canvas.getObjects().forEach(disableCache);
        try {
            return canvas.toDataURL({ format: 'png', multiplier: (quality === '4k' ? 2160 : 360) / canvas.getWidth(), enableRetinaScaling: false });
        } finally {
            cacheSettings.forEach((objectCaching, object) => object.set({ objectCaching, dirty: true }));
            canvas.requestRenderAll();
        }
    }
    return { host, ready, error, exportPreview };
}
