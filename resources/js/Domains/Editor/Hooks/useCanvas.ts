import { TEXT_WRAP_OPTIONS, textLineHeight } from '../Services/textWrapping';
import type { PresentationCard } from '../../../Types/PresentationCard';
import { capturePresentationCard } from '../Services/capturePresentationCard';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Canvas, Ellipse, FabricImage, Line, Rect, Textbox } from 'fabric';
import type { FabricObject } from 'fabric';
import type { DesignDocument } from '../../../Types/DesignDocument';
import type { CanvasGuides, EditorAsset } from '../../../Types/Editor';
import type { Layer } from '../../../Types/Layer';
import { resolveColor } from '../Services/editorDocument';
import { toFabricObject } from '../Services/FabricAdapter';
import { renderCardMaterial } from '../Services/renderCardMaterial';
import { imagePlacement } from '../Services/imagePlacement';
import { DESIGN_CONTRACT } from '../Services/designContract';
import { normalizeTextScale } from '../Services/textScaling';

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
    capturePreview: () => string | null;
    captureParts: () => PresentationCard | null;
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

/** Variable fonts need their weight range declared, or the browser fakes bold instead of using the axis. */
const variableFontSlugs = new Set(['font-reem-kufi', 'font-el-messiri']);

/**
 * `document.fonts.check` is true for a family nobody declared, so it cannot tell "loaded" from "missing";
 * look for a declared face of that family instead.
 */
function hasFontFace(family: string): boolean {
    for (const face of document.fonts) {
        if (face.family.replace(/^["']|["']$/g, '') === family) return true;
    }

    return false;
}

async function loadEditorFonts(assets: EditorAsset[], signal: AbortSignal): Promise<void> {
    const fontAssets = assets.filter((asset) => asset.type === 'font' && asset.fontFamily && asset.fontUrl);
    await Promise.all(fontAssets.map(async (asset) => {
        if (signal.aborted || !asset.fontFamily || !asset.fontUrl) return;
        if (hasFontFace(asset.fontFamily)) return;
        const face = new FontFace(asset.fontFamily, `url("${asset.fontUrl}")`, variableFontSlugs.has(asset.slug) ? { weight: '100 900' } : {});
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
        x: Math.max(-DESIGN_CONTRACT.maximumCanvasDimension, Math.min(DESIGN_CONTRACT.maximumCanvasDimension, Math.round(object.left))),
        y: Math.max(-DESIGN_CONTRACT.maximumCanvasDimension, Math.min(DESIGN_CONTRACT.maximumCanvasDimension, Math.round(object.top))),
        width: Math.max(1, Math.round(object.width)),
        height: Math.max(1, Math.round(object.height)),
        scaleX: Number(object.scaleX.toFixed(4)),
        scaleY: Number(object.scaleY.toFixed(4)),
        rotation: Number(object.angle.toFixed(2)),
        opacity: Number(object.opacity.toFixed(3)),
    };

    if (source.type === 'text' && object instanceof Textbox) {
        return { ...source, frame, fontSize: Math.round(object.fontSize), content: Array.from(object.text).slice(0, DESIGN_CONTRACT.maximumTextLength).join('') };
    }

    return { ...source, frame };
}

function contentSignature(layer: Layer): string {
    if (layer.type === 'text') return `text:${layer.content}:${layer.font.assetId}:${layer.font.version}:${layer.fontSize}:${layer.fontWeight}:${layer.alignment}:${layer.direction}:${JSON.stringify(layer.fill)}`;
    if (layer.type === 'image') return `image:${layer.asset.assetId}:${layer.asset.version}:${layer.fit}`;
    return `shape:${layer.shapeKind}:${JSON.stringify(layer.fill)}:${JSON.stringify(layer.stroke)}:${layer.strokeWidth}`;
}

function paletteSignature(design: DesignDocument): string {
    return JSON.stringify({ palette: design.palette.values, background: design.canvas.background, material: design.canvas.material });
}

export function useCanvas({ document: design, assets, selectedLayerId, onSelect, onLayerChange }: UseCanvasOptions): UseCanvasResult {
    const host = useRef<HTMLDivElement>(null);
    const canvas = useRef<Canvas | null>(null);
    const layerIds = useRef(new Map<FabricObject, string>());
    const objects = useRef(new Map<string, FabricObject>());
    const renderedContent = useRef(new Map<string, string>());
    const renderedPalette = useRef('');
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
            const availableWidth = Math.max(240, Math.min(container.clientWidth, 650));
            instance.setDimensions({ width: availableWidth, height: availableWidth * design.canvas.height / design.canvas.width }, { cssOnly: true });
            instance.calcOffset();
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
            if (target instanceof Textbox && (Math.abs(target.scaleX - 1) > 0.001 || Math.abs(target.scaleY - 1) > 0.001)) {
                const resized = normalizeTextScale({ width: target.width, fontSize: target.fontSize, scaleX: target.scaleX, scaleY: target.scaleY }, DESIGN_CONTRACT.maximumCanvasDimension);
                target.set({ width: resized.width, fontSize: resized.fontSize, scaleX: 1, scaleY: 1 });
                target.setCoords();
            }
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
            renderedContent.current.clear();
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

        const nextPalette = paletteSignature(design);
        const canUpdateInPlace = ready
            && renderedPalette.current === nextPalette
            && renderedContent.current.size === design.canvas.layers.length
            && design.canvas.layers.every((layer) => renderedContent.current.get(layer.id) === contentSignature(layer) && objects.current.has(layer.id));

        if (canUpdateInPlace) {
            synchronizing.current = true;
            for (const [index, layer] of design.canvas.layers.entries()) {
                const object = objects.current.get(layer.id);
                if (!object) continue;
                object.set(layer.type === 'image' && object instanceof FabricImage ? {
                    ...commonProperties(layer),
                    width: object.width,
                    height: object.height,
                    ...imagePlacement(layer.frame, object.width, object.height, layer.fit),
                } : commonProperties(layer));
                if (object instanceof Textbox) object.set({ editable: !layer.locked });
                object.setCoords();
                editor.moveObjectTo(object, index);
            }
            editor.requestRenderAll();
            synchronizing.current = false;
            return () => abort.abort();
        }

        setReady(false);
        setError('');
        synchronizing.current = true;

        async function renderDocument(): Promise<void> {
            try {
                const [, texture] = await Promise.all([loadEditorFonts(assets, abort.signal), renderCardMaterial(design.canvas, abort.signal)]);
                if (abort.signal.aborted) { texture?.dispose(); return; }

                editor.backgroundImage?.dispose();
                editor.clear();
                layerIds.current.clear();
                objects.current.clear();
                renderedContent.current.clear();
                editor.backgroundColor = resolveColor(design.canvas.background, design);
                editor.backgroundImage = texture;
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
                            ...TEXT_WRAP_OPTIONS,
                            lineHeight: textLineHeight(asset?.slug),
                            editable: !layer.locked,
                        });
                        (object as Textbox).setControlsVisibility({ mt: false, mb: false });
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
                            object.set({
                                ...commonProperties(layer),
                                width: object.width,
                                height: object.height,
                                ...imagePlacement(layer.frame, object.width, object.height, descriptor.fit),
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
                    renderedContent.current.set(layer.id, contentSignature(layer));
                    editor.add(object);
                }

                if (selectedLayerId) {
                    const selected = objects.current.get(selectedLayerId);
                    if (selected?.selectable) editor.setActiveObject(selected);
                }
                editor.requestRenderAll();
                renderedPalette.current = nextPalette;
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
    }, [assets, design.canvas, design.palette]);

    useEffect(() => {
        const instance = canvas.current;
        if (!instance || synchronizing.current) return;
        const object = selectedLayerId ? objects.current.get(selectedLayerId) : undefined;
        if (object?.selectable) instance.setActiveObject(object);
        else instance.discardActiveObject();
        instance.requestRenderAll();
    }, [selectedLayerId, ready]);

    const capturePreview = useCallback((): string | null => {
        const instance = canvas.current;
        if (!instance || !ready || error || synchronizing.current) return null;
        try {
            return instance.toDataURL({ format: 'png', multiplier: 1, enableRetinaScaling: false });
        } catch {
            setError('تعذّر تجهيز المعاينة. تأكدي من تحميل جميع العناصر وحاولي مجدداً.');
            return null;
        }
    }, [ready, error]);

    const captureParts = useCallback((): PresentationCard | null => {
        const instance = canvas.current;
        if (!instance || !ready || error || synchronizing.current) return null;
        try { return capturePresentationCard(instance, (object) => object instanceof Textbox); }
        catch { return null; }
    }, [ready, error]);

    return { host, ready, error, guides, capturePreview, captureParts };
}
