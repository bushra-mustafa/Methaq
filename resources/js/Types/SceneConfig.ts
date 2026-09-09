import type { AssetReference } from './Layer';
import type { ColorValue } from './Palette';

export type MotionPolicy = 'system' | 'reduced' | 'off';
export type EnvelopePresetId = 'classic-fold';
export type EffectId = 'sparkle' | 'smoke';
export type SceneBackdropPreset = 'inherit' | 'burgundy-nebula' | 'blush-cloud' | 'midnight-gold' | 'emerald-silk';

export interface EnvelopeAppearance {
    style: 'classic' | 'luxury' | 'minimal' | 'rounded' | 'gatefold';
    sealStyle: 'wax' | 'medallion' | 'methaq';
    sealX: number;
    sealY: number;
    sealSize: number;
}

export interface EnvelopeConfig {
    appearance?: EnvelopeAppearance;
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

export interface InvitationCover {
    heading: string;
    names: string;
    dateLabel: string;
    message: string;
    language: 'ar' | 'en' | 'mixed';
    decoration: 'none' | 'floral' | 'halo';
    animateText: boolean;
}

export interface SceneBackdrop {
    preset: SceneBackdropPreset;
}

export interface SceneConfig {
    cover?: InvitationCover;
    backdrop?: SceneBackdrop;
    sceneSchemaVersion: 1;
    opening: OpeningConfig;
    effects: EffectConfig[];
    audio: AudioConfig;
    motionPolicy: MotionPolicy;
}
