import type { CanvasData } from './CanvasData';
import type { Palette } from './Palette';
import type { SceneConfig } from './SceneConfig';

export interface DesignDocument {
    schemaVersion: 1;
    canvas: CanvasData;
    palette: Palette;
    scene: SceneConfig;
}

export interface SaveDesignPayload {
    document: DesignDocument;
    expectedRevision: number;
}

export interface DesignSnapshot {
    document: DesignDocument;
    revision: number;
}
