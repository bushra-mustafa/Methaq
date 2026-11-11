export interface EnvelopeTheme {
    id: string;
    colorName: string;
    category: "wedding" | "henna" | "marriage_contract" | "graduation";
    colors: {
        back: string;
        sides: string;
        bottom: string;
        flap: string;
        inner: string;
        shadow: string;
    };
    letter: {
        occasion: string;
        names: string;
        detail: string;
    };
}

export const emeraldTheme: EnvelopeTheme = {
    id: "emerald",
    colorName: "زمردي",
    category: "wedding",
    colors: {
        back: "#04301f",
        sides: "#06432f",
        bottom: "#074b36",
        flap: "#085a40",
        inner: "#053a28",
        shadow: "rgb(5 54 42 / 30%)",
    },
    letter: {
        occasion: "دعوة عرس",
        names: "محمد وأسماء",
        detail: "الخميس 14/8/2027، طرابلس",
    },
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
        category: "henna",
        colors: {
            back: "#3d170f",
            sides: "#4e1f15",
            bottom: "#5a2418",
            flap: "#6b2d1f",
            inner: "#461b12",
            shadow: "rgb(61 23 15 / 30%)",
        },
        letter: {
            occasion: "ليلة الحنّة",
            names: "نوارة",
            detail: "الأربعاء 13/8/2027، بنغازي",
        },
    },
    {
        id: "navy",
        colorName: "كحلي",
        category: "marriage_contract",
        colors: {
            back: "#0f1a2e",
            sides: "#16243f",
            bottom: "#1c2d4d",
            flap: "#22365b",
            inner: "#132036",
            shadow: "rgb(15 26 46 / 30%)",
        },
        letter: {
            occasion: "عقد قران",
            names: "عبدالسلام وخديجة",
            detail: "الجمعة 5/9/2027، مصراتة",
        },
    },
    {
        id: "ivory",
        colorName: "عاجي",
        category: "graduation",
        colors: {
            back: "#e5dccb",
            sides: "#efe9dc",
            bottom: "#f8f6ed",
            flap: "#fbf9f2",
            inner: "#ece5d4",
            shadow: "rgb(135 121 101 / 25%)",
        },
        letter: {
            occasion: "حفل تخرّج",
            names: "ريم سالم",
            detail: "دفعة 2027، جامعة طرابلس",
        },
    },
    {
        id: "olive",
        colorName: "زيتوني",
        category: "wedding",
        colors: {
            back: "#1c271d",
            sides: "#243325",
            bottom: "#2c3d2d",
            flap: "#364a37",
            inner: "#213022",
            shadow: "rgb(28 39 29 / 30%)",
        },
        letter: {
            occasion: "دعوة عرس",
            names: "مفتاح وفاطمة",
            detail: "السبت 2/10/2027، الزاوية",
        },
    },
    {
        id: "bronze",
        colorName: "برونزي",
        category: "henna",
        colors: {
            back: "#4a3a22",
            sides: "#5f4a2c",
            bottom: "#725b38",
            flap: "#846a43",
            inner: "#55432a",
            shadow: "rgb(74 58 34 / 30%)",
        },
        letter: {
            occasion: "ليلة الحنّة",
            names: "سعاد",
            detail: "الخميس 21/10/2027، سبها",
        },
    },
    {
        id: "burgundy",
        colorName: "عنابي",
        category: "marriage_contract",
        colors: {
            back: "#3f1418",
            sides: "#561c22",
            bottom: "#682329",
            flap: "#7a2c33",
            inner: "#4a181d",
            shadow: "rgb(63 20 24 / 30%)",
        },
        letter: {
            occasion: "عقد قران",
            names: "أنس ومريم",
            detail: "الجمعة 12/11/2027، طرابلس",
        },
    },
];
