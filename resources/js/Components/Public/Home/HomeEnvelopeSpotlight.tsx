import { Check } from "lucide-react";
import HomeEnvelopeShowcase from "./HomeEnvelopeShowcase";

const guestMoments = [
    "يضغط ضيفك على الختم، فينفتح الظرف وتظهر بطاقتك.",
    "موسيقى ترحيبية تختارينها من مكتبة ميثاق.",
    "يؤكد ضيفك حضوره من صفحة الدعوة نفسها.",
];

export default function HomeEnvelopeSpotlight() {
    return (
        <section
            aria-labelledby="home-envelope-title"
            className="scroll-mt-24 border-y border-methaq-gold/20 bg-methaq-paper px-6 py-20 lg:px-12 lg:py-24"
        >
            <div className="mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-12">
                <div className="flex flex-col gap-6 text-right lg:col-span-5">
                    <h2
                        id="home-envelope-title"
                        className="m-0 font-methaq-display text-[clamp(28px,3.4vw,38px)] font-normal text-balance text-methaq-primary"
                    >
                        أكثر من رابط؛ ظرف يفتحه ضيوفك
                    </h2>
                    <p className="m-0 max-w-[46ch] text-[16px] leading-[1.9] text-methaq-muted">
                        اختاري لون الظرف، ثم اضغطي على الختم لتري ما سيراه ضيوفك
                        عند فتح الدعوة.
                    </p>
                    <ul className="m-0 flex list-none flex-col gap-3 p-0">
                        {guestMoments.map((moment) => (
                            <li
                                key={moment}
                                className="flex items-start gap-3 text-[15px] leading-[1.8] text-methaq-primary"
                            >
                                <Check
                                    size={18}
                                    aria-hidden="true"
                                    className="mt-1 shrink-0 text-methaq-gold-dark"
                                />
                                {moment}
                            </li>
                        ))}
                    </ul>
                </div>

                <div className="min-w-0 lg:col-span-7">
                    <HomeEnvelopeShowcase mode="manual" />
                </div>
            </div>
        </section>
    );
}
