import { useCallback, useEffect, useRef, useState } from 'react';
import { ActiveSelection, Canvas } from 'fabric';
import type { FabricObject } from 'fabric';
import type { DesignProposal, ProposalPartId } from '../../../../../Types/DesignProposal';
import { composeIvoryInvitation } from '../Services/composeIvoryInvitation';
import { loadProposalFonts } from '../Services/ProposalDrawing';
import { descendants, PROPOSAL_WIDTH } from '../Services/proposalCatalog';
import { localMovement } from '../Services/proposalDocument';

interface Options {
    document: DesignProposal;
    selected: ProposalPartId | null;
    preview: boolean;
    onSelect: (id: ProposalPartId | null) => void;
    onMove: (id: ProposalPartId, x: number, y: number) => void;
}
export function useIvoryCanvas(options: Options) {
    const host = useRef<HTMLDivElement>(null);
    const canvas = useRef<Canvas | null>(null);
    const current = useRef(options);
    current.current = options;
    const synchronizing = useRef(false);
    const ids = useRef(new WeakMap<FabricObject, ProposalPartId>());
    const objects = useRef(new Map<ProposalPartId, FabricObject>());
    const resize = useRef<() => void>(() => undefined);
    const [ready, setReady] = useState(false);
    const [height, setHeight] = useState(1900);
    const [error, setError] = useState('');
    const [attempt, setAttempt] = useState(0);

    const restoreSelection = useCallback(() => {
        const instance = canvas.current;
        if (!instance) return;
        synchronizing.current = true;
        instance.discardActiveObject();
        instance.getObjects().forEach((object) => object.set({ selectable: !current.current.preview, evented: !current.current.preview }));
        const selected = current.current.selected;
        if (selected && !current.current.preview) {
            const members = descendants(selected).flatMap((id) => { const object = objects.current.get(id); return object ? [object] : []; });
            if (members.length === 1) instance.setActiveObject(members[0]!);
            if (members.length > 1) instance.setActiveObject(new ActiveSelection(members, { canvas: instance, hasControls: false, borderColor: '#074b36', padding: 7 }));
        }
        instance.requestRenderAll();
        synchronizing.current = false;
    }, []);

    useEffect(() => {
        const container = host.current;
        if (!container) return;
        const element = window.document.createElement('canvas');
        element.setAttribute('aria-label', 'دعوة الزفاف العاجية القابلة للتعديل');
        container.appendChild(element);
        const instance = new Canvas(element, { width: PROPOSAL_WIDTH, height: 1900, enableRetinaScaling: false, preserveObjectStacking: true, selection: false, backgroundColor: '#F8F5EE' });
        canvas.current = instance;
        resize.current = () => {
            const width = Math.max(240, Math.min(container.clientWidth, 650));
            instance.setDimensions({ width, height: width * instance.height / PROPOSAL_WIDTH }, { cssOnly: true });
            instance.calcOffset();
        };
        const observer = new ResizeObserver(() => resize.current());
        observer.observe(container);
        let start: { x: number; y: number; id: ProposalPartId } | null = null;
        const selected = (target?: FabricObject) => {
            if (!synchronizing.current) current.current.onSelect(target ? ids.current.get(target) ?? current.current.selected : null);
        };
        instance.on('selection:created', (event) => selected(event.selected[0]));
        instance.on('selection:updated', (event) => selected(event.selected[0]));
        instance.on('selection:cleared', () => selected());
        instance.on('before:transform', ({ transform }) => {
            const targetId = ids.current.get(transform.target);
            const selectedId = current.current.selected;
            const id = selectedId && (!targetId || descendants(selectedId).includes(targetId)) ? selectedId : targetId;
            if (id) start = { id, x: transform.target.left, y: transform.target.top };
        });
        instance.on('object:modified', ({ target }) => {
            if (!start || synchronizing.current) return;
            const delta = localMovement(current.current.document, start.id, target.left - start.x, target.top - start.y);
            current.current.onMove(start.id, delta.x, delta.y);
            start = null;
        });
        return () => {
            observer.disconnect();
            canvas.current = null;
            void instance.dispose().catch(() => undefined);
            container.replaceChildren();
        };
    }, []);

    useEffect(() => {
        const instance = canvas.current;
        if (!instance) return;
        let cancelled = false;
        setReady(false);
        setError('');
        void (async () => {
            try {
                await loadProposalFonts();
                const result = await composeIvoryInvitation(options.document);
                if (cancelled) { result.drawing.objects.forEach(({ object }) => object.dispose()); return; }
                synchronizing.current = true;
                instance.discardActiveObject();
                const old = instance.getObjects();
                instance.remove(...old);
                old.forEach((object) => object.dispose());
                objects.current.clear();
                for (const { id, object } of result.drawing.objects) { objects.current.set(id, object); ids.current.set(object, id); instance.add(object); }
                instance.setDimensions({ width: PROPOSAL_WIDTH, height: result.height });
                resize.current();
                setHeight(result.height);
                restoreSelection();
                synchronizing.current = false;
                setReady(true);
            } catch (error: unknown) {
                if (!cancelled) { synchronizing.current = false; setError(error instanceof Error ? error.message : 'تعذّر تجهيز الدعوة.'); }
            }
        })();
        return () => { cancelled = true; };
    }, [options.document, attempt, restoreSelection]);

    useEffect(restoreSelection, [options.selected, options.preview, restoreSelection]);

    const capture = useCallback((): string | null => {
        if (!canvas.current || !ready || error) return null;
        return canvas.current.toDataURL({ format: 'png', multiplier: 2, enableRetinaScaling: false });
    }, [ready, error]);
    return { host, ready, height, error, capture, retry: () => setAttempt((value) => value + 1) };
}
