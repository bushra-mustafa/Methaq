import type { Layer } from './Layer';
import type { CardMaterial } from './CardMaterial';
import type { ColorValue } from './Palette';

export interface CanvasData {
    schemaVersion: 1;
    width: 1080;
    height: 1920;
    background: ColorValue;
    material?: CardMaterial;
    layers: Layer[];
}
