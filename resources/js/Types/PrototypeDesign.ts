export type NameLanguage = 'ar' | 'en';
export type TextSlot = 'intro' | 'names' | 'blessing' | 'date';
export interface TextPosition { x: number; y: number }
export interface PrototypeDesign {
    schemaVersion: 2;
    paper: string;
    accent: string;
    envelope: string;
    seal: string;
    entrance: 'envelope' | 'direct';
    florals: boolean;
    background: string;
    ink: string;
    sparkle: string;
    intensity: number;
    motion: boolean;
    arabicNames: string;
    englishNames: string;
    language: NameLanguage;
    blessing: string;
    dateLabel: string;
    positions: Record<TextSlot, TextPosition>;
}
