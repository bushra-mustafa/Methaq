import type { MotionPolicy } from './SceneConfig';

export type EnvelopeMotionPhase = 'sealed' | 'opening' | 'names-preview' | 'revealing' | 'opened';

export interface EnvelopeMotionSceneProps {
    cardUrl: string;
    title: string;
    names: string;
    dateLabel: string;
    language: 'ar' | 'en' | 'mixed';
    motionPolicy: MotionPolicy;
}
