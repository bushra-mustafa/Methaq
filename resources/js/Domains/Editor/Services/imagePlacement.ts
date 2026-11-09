import type { ImageFit, LayerFrame } from '../../../Types/Layer';

export function imagePlacement(frame: LayerFrame, width: number, height: number, fit: ImageFit) {
    const targetWidth = frame.width * frame.scaleX;
    const targetHeight = frame.height * frame.scaleY;
    const widthRatio = targetWidth / Math.max(1, width);
    const heightRatio = targetHeight / Math.max(1, height);
    const scale = fit === 'cover' ? Math.max(widthRatio, heightRatio) : Math.min(widthRatio, heightRatio);

    return {
        left: frame.x + (targetWidth - width * scale) / 2,
        top: frame.y + (targetHeight - height * scale) / 2,
        scaleX: scale,
        scaleY: scale,
    };
}
