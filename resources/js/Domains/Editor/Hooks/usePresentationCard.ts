import type { PresentationCard } from '../../../Types/PresentationCard';
import { useEffect, useState } from 'react';
import type { DesignDocument } from '../../../Types/DesignDocument';
import type { EditorAsset } from '../../../Types/Editor';
import { useCanvas } from './useCanvas';

const ignoreCanvasInteraction = (): void => {};

export function usePresentationCard(document: DesignDocument, assets: EditorAsset[]) {
    const canvas = useCanvas({ document, assets, selectedLayerId: null, onSelect: ignoreCanvasInteraction, onLayerChange: ignoreCanvasInteraction });
    const [cardUrl, setCardUrl] = useState<string | null>(null);
    const [parts, setParts] = useState<PresentationCard | null>(null);
    useEffect(() => {
        if (!canvas.ready || canvas.error) return;
        const image = canvas.capturePreview();
        if (image) { setCardUrl(image); setParts(canvas.captureParts()); }
    }, [canvas.ready, canvas.error, canvas.capturePreview, canvas.captureParts, document.canvas, document.palette]);
    return { host: canvas.host, cardUrl, parts, ready: canvas.ready, error: canvas.error };
}
