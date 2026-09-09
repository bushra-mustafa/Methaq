import type { AssetReference } from './Layer';
import type { ColorValue } from './Palette';

export type MotionPolicy = 'system' | 'reduced' | 'off';
export type EnvelopePresetId = 'classic-fold';
export type EffectId = 'sparkle' | 'smoke';

export interface EnvelopeConfig {
    presetId: EnvelopePresetId;
    presetVersion: 1;
    paperColor: ColorValue;
    liningColor: ColorValue;
    sealColor: ColorValue;
    monogram: string;
}

export type OpeningConfig =
    | { type: 'direct'; durationMs: 0 }
    | { type: 'fade'; durationMs: number }
    | { type: 'envelope'; durationMs: number; envelope: EnvelopeConfig };

export interface EffectConfig {
    effectId: EffectId;
    effectVersion: 1;
    enabled: boolean;
    color: ColorValue;
    intensity: number;
    speed: number;
}

export interface AudioConfig {
    enabled: boolean;
    asset: AssetReference | null;
    volume: number;
}

export interface SceneConfig {
    sceneSchemaVersion: 1;
    opening: OpeningConfig;
    effects: EffectConfig[];
    audio: AudioConfig;
    motionPolicy: MotionPolicy;
}
