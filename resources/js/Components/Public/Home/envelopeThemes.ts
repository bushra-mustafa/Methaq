import type { EnvelopeAppearance } from "../../../Types/SceneConfig";
import type { EnvelopeColors } from "../../../Domains/Editor/Services/scenePresentation";

export interface EnvelopeTheme {
    id: string;
    colorName: string;
    colors: EnvelopeColors;
    /** A real envelope and seal shape from the editor, so the showcase only promises what owners can pick. */
    appearance: EnvelopeAppearance;
    letter: {
        names: string;
        detail: string;
    };
}

const SEAL_POSITION = { sealX: 50, sealY: 52, sealSize: 20 } as const;
const GOLD = "#b69a50";

export const emeraldTheme: EnvelopeTheme = {
    id: "emerald",
    colorName: "زمردي",
    colors: { paper: "#074b36", lining: "#f6fbf2", seal: GOLD },
    appearance: { style: "classic", sealStyle: "wax", ...SEAL_POSITION },
    letter: { names: "محمد وأسماء", detail: "طرابلس · 14/8/2027" },
};

/**
 * Envelope colours shown on the home page: the Methaq brand colours first,
 * then the palette chosen in the Stitch "Editorial Serenity" reference
 * (olive #2c3d2d, bronze #725b38 and burgundy #682329).
 */
export const envelopeThemes: EnvelopeTheme[] = [
    emeraldTheme,
    {
        id: "henna",
        colorName: "حنّائي",
        colors: { paper: "#5a2418", lining: "#fbf1e4", seal: "#d1a867" },
        appearance: { style: "luxury", sealStyle: "wax-heart", ...SEAL_POSITION },
        letter: { names: "نوارة", detail: "بنغازي · 13/8/2027" },
    },
    {
        id: "navy",
        colorName: "كحلي",
        colors: { paper: "#1c2d4d", lining: "#f3f1ea", seal: "#c9ab69" },
        appearance: { style: "minimal", sealStyle: "medallion", ...SEAL_POSITION },
        letter: { names: "عبدالسلام وخديجة", detail: "مصراتة · 5/9/2027" },
    },
    {
        id: "ivory",
        colorName: "عاجي",
        colors: { paper: "#f1ebdd", lining: "#fffdf7", seal: "#074b36" },
        appearance: { style: "rounded", sealStyle: "wax-flower", ...SEAL_POSITION },
        letter: { names: "ريم سالم", detail: "جامعة طرابلس · دفعة 2027" },
    },
    {
        id: "olive",
        colorName: "زيتوني",
        colors: { paper: "#2c3d2d", lining: "#f4f1e6", seal: GOLD },
        appearance: { style: "scalloped", sealStyle: "wax-oval", ...SEAL_POSITION },
        letter: { names: "مفتاح وفاطمة", detail: "الزاوية · 2/10/2027" },
    },
    {
        id: "bronze",
        colorName: "برونزي",
        colors: { paper: "#725b38", lining: "#fbf5e8", seal: "#e2c88f" },
        appearance: { style: "gatefold", sealStyle: "medallion-octagon", ...SEAL_POSITION },
        letter: { names: "سعاد", detail: "سبها · 21/10/2027" },
    },
    {
        id: "burgundy",
        colorName: "عنابي",
        colors: { paper: "#682329", lining: "#fdf3ee", seal: "#d8b982" },
        appearance: { style: "wallet", sealStyle: "wax-square", ...SEAL_POSITION },
        letter: { names: "عمر ومريم", detail: "طرابلس · 12/11/2027" },
    },
];
