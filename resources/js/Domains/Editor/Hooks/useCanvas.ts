import { useEffect, useRef, useState } from 'react';
import { Canvas, Ellipse, FabricImage, Line, Rect, Textbox } from 'fabric';
import type { FabricObject } from 'fabric';
import type { DesignDocument } from '../../../Types/DesignDocument';
import type { CanvasGuides, EditorAsset } from '../../../Types/Editor';
import type { Layer } from '../../../Types/Layer';
import { resolveColor } from '../Services/editorDocument';
import { toFabricObject } from '../Services/FabricAdapter';

interface UseCanvasOptions {
    document: DesignDocument;
    assets: EditorAsset[];
    selectedLayerId: string | null;
    onSelect: (layerId: string | null) => void;
    onLayerChange: (layerId: string, layer: Layer) => void;
}

interface UseCanvasResult {
    host: React.RefObject<HTMLDivElement | null>;
    ready: boolean;
    error: string;
    guides: CanvasGuides;
}

const SNAP_DISTANCE = 12;

function closestGuide(points: number[], guides: number[]): { offset: number; guide: number } | null {
    let closest: { offset: number; guide: number } | null = null;
    for (const point of points) {
        for (const guide of guides) {
            const offset = guide - point;
            if (Math.abs(offset) <= SNAP_DISTANCE && (closest === null || Math.abs(offset) < Math.abs(closest.offset))) closest = { offset, guide };
        }
    }
    return closest;
}

function fontFamily(asset: EditorAsset | undefined): string {
    return asset?.fontFamily ?? 'Methaq Arabic';
}

async function loadEditorFonts(assets: EditorAsset[], signal: AbortSignal): Promise<void> {
    const fontAssets = assets.filter((asset) => asset.type === 'font' && asset.fontFamily && asset.fontUrl);
    await Promise.all(fontAssets.map(async (asset) => {
        if (signal.aborted || !asset.fontFamily || !asset.fontUrl) return;
        if (document.fonts.check(`16px "${asset.fontFamily}"`)) return;
        const face = new FontFace(asset.fontFamily, `url("${asset.fontUrl}")`);
        const loaded = await face.load();
        if (!signal.aborted) document.fonts.add(loaded);
    }));
}

function commonProperties(layer: Layer) {
    return {
        left: layer.frame.x,
        top: layer.frame.y,
        width: layer.frame.width,
        height: layer.frame.height,
        scaleX: layer.frame.scaleX,
        scaleY: layer.frame.scaleY,
        angle: layer.frame.rotation,
        opacity: layer.frame.opacity,
        visible: layer.visible,
        selectable: !layer.locked,
        evented: !layer.locked,
        lockMovementX: layer.locked,
        lockMovementY: layer.locked,
        lockRotation: layer.locked,
        lockScalingX: layer.locked,
        lockScalingY: layer.locked,
        borderColor: '#b69a50',
        cornerColor: '#074b36',
        cornerStyle: 'circle' as const,
        transparentCorners: false,
        objectCaching: false,
    };
}

function layerFromObject(source: Layer, object: FabricObject): Layer {
    const frame = {
        x: Math.round(object.left),
        y: Math.round(object.top),
        width: Math.max(1, Math.round(object.width)),
        height: Math.max(1, Math.round(object.height)),
        scaleX: Number(object.scaleX.toFixed(4)),
        scaleY: Number(object.scaleY.toFixed(4)),
        rotation: Number(object.angle.toFixed(2)),
        opacity: Number(object.opacity.toFixed(3)),
    };

    if (source.type === 'text' && object instanceof Textbox) {
        return { ...source, frame, content: object.text };
    }

    return { ...source, frame };
}

export function useCanvas({ document: design, assets, selectedLayerId, onSelect, onLayerChange }: UseCanvasOptions): UseCanvasResult {
    const host = useRef<HTMLDivElement>(null);
    const canvas = useRef<Canvas | null>(null);
    const layerIds = useRef(new Map<FabricObject, string>());
    const objects = useRef(new Map<string, FabricObject>());
    const latestDesign = useRef(design);
    const latestSelect = useRef(onSelect);
    const latestChange = useRef(onLayerChange);
    const synchronizing = useRef(false);
    const [ready, setReady] = useState(false);
    const [error, setError] = useState('');
    const [guides, setGuides] = useState<CanvasGuides>({ horizontal: null, vertical: null });
    latestDesign.current = design;
    latestSelect.current = onSelect;
    latestChange.current = onLayerChange;

    useEffect(() => {
        const container = host.current;
        if (!container) return;

        const element = document.createElement('canvas');
        element.setAttribute('aria-label', 'مساحة تصميم بطاقة ميثاق');
        container.appendChild(element);
        const instance = new Canvas(element, {
            width: design.canvas.width,
            height: design.canvas.height,
            enableRetinaScaling: false,
            preserveObjectStacking: true,
            selection: false,
        });
        canvas.current = instance;

        const resize = (): void => {
            const availableWidth = Math.max(260, Math.min(container.clientWidth, 560));
            instance.setDimensions({ width: availableWidth, height: availableWidth * design.canvas.height / design.canvas.width }, { cssOnly: true });
        };
        resize();
        const observer = new ResizeObserver(resize);
        observer.observe(container);

        const selectTarget = (target?: FabricObject): void => {
            if (synchronizing.current) return;
            latestSelect.current(target ? layerIds.current.get(target) ?? null : null);
        };
        instance.on('selection:created', ({ selected }) => selectTarget(selected[0]));
        instance.on('selection:updated', ({ selected }) => selectTarget(selected[0]));
        instance.on('selection:cleared', () => selectTarget());
        instance.on('object:moving', ({ target }) => {
            const bounds = target.getBoundingRect();
            const xPoints = [bounds.left, bounds.left + bounds.width / 2, bounds.left + bounds.width];
            const yPoints = [bounds.top, bounds.top + bounds.height / 2, bounds.top + bounds.height];
            const xGuides = [latestDesign.current.canvas.width / 2];
            const yGuides = [latestDesign.current.canvas.height / 2];
            for (const object of instance.getObjects()) {
                if (object === target || !object.visible) continue;
                const objectBounds = object.getBoundingRect();
                xGuides.push(objectBounds.left, objectBounds.left + objectBounds.width / 2, objectBounds.left + objectBounds.width);
                yGuides.push(objectBounds.top, objectBounds.top + objectBounds.height / 2, objectBounds.top + objectBounds.height);
            }
            const vertical = closestGuide(xPoints, xGuides);
            const horizontal = closestGuide(yPoints, yGuides);
            if (vertical) target.set({ left: target.left + vertical.offset });
            if (horizontal) target.set({ top: target.top + horizontal.offset });
            setGuides({ horizontal: horizontal?.guide ?? null, vertical: vertical?.guide ?? null });
        });
        const persistObject = (target: FabricObject): void => {
            if (synchronizing.current) return;
            const id = layerIds.current.get(target);
            const source = latestDesign.current.canvas.layers.find((layer) => layer.id === id);
            if (id && source) latestChange.current(id, layerFromObject(source, target));
            setGuides({ horizontal: null, vertical: null });
        };
        instance.on('object:modified', ({ target }) => persistObject(target));
        instance.on('mouse:up', () => setGuides({ horizontal: null, vertical: null }));

        return () => {
            observer.disconnect();
            canvas.current = null;
            layerIds.current.clear();
            objects.current.clear();
            void instance.dispose().catch(() => undefined);
            container.replaceChildren();
        };
    }, []);

    useEffect(() => {
        const instance = canvas.current;
        if (!instance) return;
        const editor = instance;

        const abort = new AbortController();
        const assetMap = new Map(assets.map((asset) => [asset.id, asset]));
        setReady(false);
        setError('');
        synchronizing.current = true;

        async function renderDocument(): Promise<void> {
            try {
                await loadEditorFonts(assets, abort.signal);
                if (abort.signal.aborted) return;

                editor.clear();
                layerIds.current.clear();
                objects.current.clear();
                editor.backgroundColor = resolveColor(design.canvas.background, design);
                let failedAssets = 0;

                for (const layer of design.canvas.layers) {
                    if (abort.signal.aborted) return;
                    const descriptor = toFabricObject(layer, design.palette);
                    let object: FabricObject;
                    if (descriptor.fabricType === 'textbox') {
                        const asset = assetMap.get(descriptor.fontAssetId);
                        object = new Textbox(descriptor.text, {
                            ...commonProperties(layer),
                            fontFamily: fontFamily(asset),
                            fontSize: descriptor.fontSize,
                            fontWeight: descriptor.fontWeight,
                            textAlign: descriptor.textAlign,
                            direction: descriptor.direction,
                            fill: descriptor.fill,
                            splitByGrapheme: true,
                            lineHeight: 1.25,
                            editable: !layer.locked,
                        });
                        (object as Textbox).on('editing:exited', () => {
                            const textbox = object as Textbox;
                            const id = layerIds.current.get(textbox);
                            const source = latestDesign.current.canvas.layers.find((candidate) => candidate.id === id);
                            if (id && source) latestChange.current(id, layerFromObject(source, textbox));
                        });
                    } else if (descriptor.fabricType === 'image') {
                        const asset = assetMap.get(descriptor.assetId);
                        try {
                            if (!asset) throw new Error('Missing asset');
                            object = await FabricImage.fromURL(asset.previewUrl, { crossOrigin: 'anonymous', signal: abort.signal });
                            const intendedWidth = layer.frame.width * layer.frame.scaleX;
                            const intendedHeight = layer.frame.height * layer.frame.scaleY;
                            const widthRatio = intendedWidth / Math.max(1, object.width);
                            const heightRatio = intendedHeight / Math.max(1, object.height);
                            const scale = descriptor.fit === 'cover' ? Math.max(widthRatio, heightRatio) : Math.min(widthRatio, heightRatio);
                            const renderedWidth = object.width * scale;
                            const renderedHeight = object.height * scale;
                            object.set({
                                ...commonProperties(layer),
                                width: object.width,
                                height: object.height,
                                left: layer.frame.x + (intendedWidth - renderedWidth) / 2,
                                top: layer.frame.y + (intendedHeight - renderedHeight) / 2,
                                scaleX: scale,
                                scaleY: scale,
                            });
                        } catch (exception: unknown) {
                            if (abort.signal.aborted) return;
                            failedAssets += 1;
                            object = new Rect({ ...commonProperties(layer), fill: '#f0e8dc', stroke: '#9c8450', strokeDashArray: [18, 12], strokeWidth: 4 });
                        }
                    } else if (descriptor.fabricType === 'ellipse') {
                        object = new Ellipse({ ...commonProperties(layer), rx: layer.frame.width / 2, ry: layer.frame.height / 2, fill: descriptor.fill, stroke: descriptor.stroke, strokeWidth: descriptor.strokeWidth });
                    } else if (descriptor.fabricType === 'line') {
                        object = new Line([0, layer.frame.height / 2, layer.frame.width, layer.frame.height / 2], { ...commonProperties(layer), fill: descriptor.fill, stroke: descriptor.stroke, strokeWidth: descriptor.strokeWidth });
                    } else {
                        object = new Rect({ ...commonProperties(layer), fill: descriptor.fill, stroke: descriptor.stroke, strokeWidth: descriptor.strokeWidth });
                    }

                    layerIds.current.set(object, layer.id);
                    objects.current.set(layer.id, object);
                    editor.add(object);
                }

                if (selectedLayerId) {
                    const selected = objects.current.get(selectedLayerId);
                    if (selected?.selectable) editor.setActiveObject(selected);
                }
                editor.requestRenderAll();
                if (failedAssets > 0) setError(`تعذّر عرض ${failedAssets} من العناصر. يمكنك استبدالها من المكتبة.`);
                setReady(true);
            } catch (exception: unknown) {
                if (!abort.signal.aborted) setError('تعذّر تجهيز مساحة التصميم. حدّث الصفحة للمحاولة مجدداً.');
            } finally {
                if (!abort.signal.aborted) synchronizing.current = false;
            }
        }

        void renderDocument();
        return () => abort.abort();
    }, [assets, design]);

    useEffect(() => {
        const instance = canvas.current;
        if (!instance || synchronizing.current) return;
        const object = selectedLayerId ? objects.current.get(selectedLayerId) : undefined;
        if (object?.selectable) instance.setActiveObject(object);
        else instance.discardActiveObject();
        instance.requestRenderAll();
    }, [selectedLayerId, ready]);

    return { host, ready, error, guides };
}
