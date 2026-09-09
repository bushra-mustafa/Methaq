export const PALETTE_ROLES = [
    'background',
    'surface',
    'primaryText',
    'secondaryText',
    'accent',
    'effect',
] as const;

export type PaletteRole = (typeof PALETTE_ROLES)[number];
export type HexColor = `#${string}`;

export type ColorValue =
    | { source: 'palette'; role: PaletteRole }
    | { source: 'literal'; value: HexColor };

export interface Palette {
    schemaVersion: 1;
    values: Record<PaletteRole, HexColor>;
}
