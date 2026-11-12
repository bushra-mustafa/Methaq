import { Check } from "lucide-react";
import Reveal from "../Reveal";
import HomeEnvelopeShowcase from "./HomeEnvelopeShowcase";

const guestMoments: { text: string; comingSoon?: boolean }[] = [
    { text: "يفتح ضيفك الرابط فيستقبله ظرفك بختم ميثاق وألوانك." },
    { text: "تنكشف بطاقتك على موسيقى ترحيبية تختارينها." },
    { text: "يؤكد حضوره من صفحة الدعوة نفسها.", comingSoon: true },
];

export default function HomeEnvelopeSpotlight() {
    return (
        <section
            id="home-envelope"
            aria-labelledby="home-envelope-title"
            className="scroll-mt-24 border-y border-methaq-border bg-methaq-paper px-6 py-20 lg:px-12 lg:py-24"
        >
            <div className="mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-12">
                <Reveal className="flex flex-col gap-6 text-right lg:col-span-5">
                    <span className="inline-flex items-center gap-2 self-start rounded-full border border-methaq-gold/40 bg-methaq-cream px-3.5 py-1 text-[12px] font-semibold text-methaq-gold-dark">
                        <span
                            aria-hidden="true"
                            className="size-2 rounded-full bg-methaq-gold motion-safe:animate-methaq-soft-pulse"
                        />
                        جرّبيها بنفسك
                    </span>
                    <h2
                        id="home-envelope-title"
                        className="m-0 font-methaq-display text-[32px] leading-tight font-bold text-balance text-methaq-primary md:text-[38px]"
                    >
                        رابطٌ واحد… وتجربةٌ يعيشها ضيوفك
                    </h2>
                    <p className="m-0 max-w-[46ch] text-[16px] leading-[1.9] text-methaq-muted">
                        دعوتك في ميثاق صفحة كاملة لمناسبتك، تبدأ بظرف يُفتح
                        وتنتهي ببطاقتك وتفاصيل فرحتك. اختاري لوناً واضغطي على
                        الختم لتري ما سيراه ضيوفك.
                    </p>
                    <ul className="m-0 flex list-none flex-col gap-3 p-0">
                        {guestMoments.map((moment) => (
                            <li
                                key={moment.text}
                                className="flex items-start gap-3 text-[15px] leading-[1.8] text-methaq-primary"
                            >
                                <Check
                                    size={18}
                                    aria-hidden="true"
                                    className="mt-1 shrink-0 text-methaq-gold-dark"
                                />
                                <span>
                                    {moment.text}
                                    {moment.comingSoon && (
                                        <span className="ms-2 inline-block rounded-full border border-methaq-gold/40 bg-methaq-cream px-2 py-0.5 align-middle text-[11px] font-semibold text-methaq-gold-dark">
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
