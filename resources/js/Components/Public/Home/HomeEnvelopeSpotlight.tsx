import { Check } from "lucide-react";
import Reveal from "../Reveal";
import HomeEnvelopeShowcase from "./HomeEnvelopeShowcase";

const guestMoments: { text: string; comingSoon?: boolean }[] = [
    { text: "يفتح ضيفك الرابط فيستقبله ظرفك بختم ميثاق وألوانك." },
    { text: "تنكشف بطاقتك على موسيقى ترحيبية تختارينها." },
    { text: "يؤكد حضوره من صفحة الدعوة نفسها.", comingSoon: true },
];

/** The guests' moment, on the dark ink band so the envelope colours stand out. */
export default function HomeEnvelopeSpotlight() {
    return (
        <section
            id="home-envelope"
            aria-labelledby="home-envelope-title"
            className="scroll-mt-24 bg-methaq-primary px-6 py-methaq-section text-methaq-cream [--methaq-focus-ring:var(--color-methaq-cream)] lg:px-12"
        >
            <div className="mx-auto grid max-w-7xl items-center gap-14 lg:grid-cols-12">
                <Reveal className="flex flex-col gap-7 lg:col-span-5">
                    <h2
                        id="home-envelope-title"
                        className="m-0 font-methaq-display text-methaq-h2 font-bold tracking-[-0.01em] text-balance text-methaq-cream"
                    >
                        رابطٌ واحد… وتجربةٌ يعيشها ضيوفك
                    </h2>
                    <p className="m-0 max-w-[44ch] text-methaq-lead text-pretty text-methaq-mist">
                        دعوتك في ميثاق صفحة كاملة لمناسبتك، تبدأ بظرف يُفتح
                        وتنتهي ببطاقتك وتفاصيل فرحتك. اختاري لوناً واضغطي على
                        الختم لتري ما سيراه ضيوفك.
                    </p>
                    <ul className="m-0 flex list-none flex-col gap-4 border-t border-methaq-gold-light/25 p-0 pt-7">
                        {guestMoments.map((moment) => (
                            <li
                                key={moment.text}
                                className="flex items-start gap-3 text-[16px] leading-[1.9] text-methaq-cream"
                            >
                                <Check
                                    size={18}
                                    aria-hidden="true"
                                    className="mt-1.5 shrink-0 text-methaq-gold-light"
                                />
                                <span>
                                    {moment.text}
                                    {moment.comingSoon && (
                                        <span className="ms-2 inline-block rounded-full border border-methaq-gold-light/50 px-2.5 py-0.5 align-middle text-[12px] font-semibold text-methaq-gold-light">
                                            قريباً
                                        </span>
                                    )}
                                </span>
                            </li>
                        ))}
                    </ul>
                </Reveal>

                <div className="min-w-0 lg:col-span-7">
                    <HomeEnvelopeShowcase mode="manual" />
                </div>
            </div>
        </section>
    );
}
