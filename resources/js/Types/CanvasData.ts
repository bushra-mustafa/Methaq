import type { Layer } from './Layer';
import type { ColorValue } from './Palette';

export interface CanvasData {
    schemaVersion: 1;
    width: 1080;
    height: 1920;
    background: ColorValue;
    layers: Layer[];
}
