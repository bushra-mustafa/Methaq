export interface AuthPromo {
    heading: string;
    body: string;
}

/** The pitch beside the form; each page picks the line that fits its moment. */
export const authPromos = {
    login: {
        heading: "دعواتك بانتظارك",
        body: "ادخلي وكمّلي من حيث وقفتِ؛ تصاميمك محفوظة وروابط مناسباتك جاهزة.",
    },
    register: {
        heading: "ابدئي دعوتك… بلا التزام",
        body: "صمّمي بطاقتك وجرّبي الظرف أولاً، ولا تدفعين إلا عندما تقرّرين النشر.",
    },
    account: {
        heading: "عهدٌ يبدأ بتفصيلة",
        body: "دعوة إلكترونية بظرف يُفتح، تحمل لون مناسبتك وكلماتك وطريقتك في استقبال من تحبين.",
    },
} as const satisfies Record<string, AuthPromo>;

export type AuthPromoKey = keyof typeof authPromos;

/** The promises that make a visitor curious enough to try the product. */
export const authPromises: string[] = [
    "اختاري قالباً وعدّلي نصوصه وألوانه وخطوطه كما تحبين.",
    "شاهدي ظرفك يُفتح كما سيراه ضيوفك، قبل أن تنشري.",
    "ادفعي بالتحويل البنكي عند النشر فقط، بلا اشتراك.",
];
