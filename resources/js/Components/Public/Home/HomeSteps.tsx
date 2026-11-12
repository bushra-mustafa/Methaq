import { useEffect, useRef, useState } from "react";
import { Check, Palette, PenLine, Send } from "lucide-react";
import type { LucideIcon } from "lucide-react";
import Reveal from "../Reveal";

interface Step {
    numeral: string;
    icon: LucideIcon;
    title: string;
    description: string;
    detail: string;
}

const steps: Step[] = [
    {
        numeral: "01",
        icon: Palette,
        title: "اختاري قالباً لمناسبتك",
        description:
            "زفاف أو حنّة أو عقد قران أو تخرج، ولكل قالب ألوانه وزخارفه.",
        detail: "معاينة حية للبطاقة والظرف",
    },
    {
        numeral: "02",
        icon: PenLine,
        title: "خصّصي البطاقة والظرف",
        description:
            "غيّري النصوص والألوان والخطوط، واختاري شكل الظرف والختم والموسيقى.",
        detail: "كل التفاصيل بألوانك",
    },
    {
        numeral: "03",
        icon: Send,
        title: "انشري وشاركي الرابط",
        description:
            "لمناسبتك رابط خاص تشاركينه مع ضيوفك، فيفتحون ظرفك وتنكشف بطاقتك.",
        detail: "رابط واحد لكل ضيوفك",
    },
];

const STEP_MS = 1600;
const FINAL_HOLD_MS = 2200;

function usePrefersReducedMotion(): boolean {
    const [reduced, setReduced] = useState(
        () => window.matchMedia("(prefers-reduced-motion: reduce)").matches,
    );

    useEffect(() => {
        const media = window.matchMedia("(prefers-reduced-motion: reduce)");
        const update = (): void => setReduced(media.matches);
        media.addEventListener("change", update);

        return () => media.removeEventListener("change", update);
    }, []);

    return reduced;
}

/** Walks the visitor through the three steps: the gold line travels 1 → 2 → 3, then starts again. */
export default function HomeSteps() {
    const list = useRef<HTMLDivElement>(null);
    const reducedMotion = usePrefersReducedMotion();
    const [inView, setInView] = useState(false);
    const [active, setActive] = useState(0);
    const lastStep = steps.length - 1;
    const current = reducedMotion ? lastStep : active;

    useEffect(() => {
        const target = list.current;
        if (!target) {
            return;
        }
        const observer = new IntersectionObserver(
            ([entry]) => setInView(entry?.isIntersecting ?? false),
            { threshold: 0.4 },
        );
        observer.observe(target);

        return () => observer.disconnect();
    }, []);

    useEffect(() => {
        if (!inView || reducedMotion) {
            return;
        }
        const timer = window.setTimeout(
            () => setActive((step) => (step === lastStep ? 0 : step + 1)),
            active === lastStep ? FINAL_HOLD_MS : STEP_MS,
        );

        return () => window.clearTimeout(timer);
    }, [active, inView, reducedMotion, lastStep]);

    return (
        <section
            id="home-steps"
            aria-labelledby="home-steps-title"
            className="scroll-mt-24 px-6 py-24 lg:px-12"
        >
            <div className="mx-auto flex max-w-7xl flex-col gap-16">
                <Reveal className="mx-auto flex max-w-2xl flex-col gap-3 text-center">
                    <span className="text-[13px] font-semibold tracking-wider text-methaq-gold-dark">
                        ثلاث خطوات فقط
                    </span>
                    <h2
                        id="home-steps-title"
                        className="m-0 font-methaq-display text-[34px] font-bold text-methaq-primary md:text-[40px]"
                    >
                        كيف تصل دعوتك
                    </h2>
                    <p className="m-0 text-[16px] leading-relaxed text-methaq-muted">
                        من اختيار القالب إلى رابط يفتحه ضيوفك، بدون تعقيد.
                    </p>
                </Reveal>

                <div ref={list} className="flex flex-col gap-10">
                <div aria-hidden="true" className="relative hidden h-4 md:block">
                    <span className="absolute inset-x-[16.66%] top-1/2 h-0.5 -translate-y-1/2 rounded-full bg-methaq-gold/20">
                        <span
                            className="absolute inset-y-0 start-0 rounded-full bg-methaq-gold transition-[width] duration-[1200ms] ease-in-out motion-reduce:transition-none"
                            style={{ width: `${(current / lastStep) * 100}%` }}
                        />
                    </span>
                    {steps.map((step, index) => (
                        <span
                            key={step.numeral}
                            data-reached={index <= current}
                            className="absolute top-0 -ms-2 size-4 rounded-full border-2 border-methaq-gold/40 bg-methaq-ivory transition-[background-color,border-color,scale] duration-500 data-[reached=true]:scale-110 data-[reached=true]:border-methaq-gold data-[reached=true]:bg-methaq-gold motion-reduce:transition-none"
                            style={{ insetInlineStart: `${16.66 + index * 33.33}%` }}
                        />
                    ))}
                </div>

                <ol className="relative m-0 grid list-none grid-cols-1 gap-8 p-0 md:grid-cols-3">
                    {steps.map((step, index) => {
                        const Icon = step.icon;
                        const reached = index <= current;
                        const isCurrent = index === current;

                        return (
                            <li
                                key={step.numeral}
                                data-reached={reached}
                                aria-current={isCurrent ? "step" : undefined}
                                className="group/step relative flex flex-col gap-5 rounded-2xl border border-methaq-border bg-methaq-paper p-8 transition-[border-color,box-shadow,translate] duration-500 data-[reached=true]:border-methaq-gold/50 aria-[current=step]:-translate-y-1.5 aria-[current=step]:shadow-methaq-raised motion-reduce:transition-none motion-reduce:aria-[current=step]:translate-y-0"
                            >
                                <div className="flex items-center justify-between">
                                    <span className="font-methaq-display text-[36px] leading-none font-bold text-methaq-gold/40 tabular-nums transition-colors duration-500 group-data-[reached=true]/step:text-methaq-gold">
                                        {step.numeral}
                                    </span>
                                    <span className="grid size-13 place-items-center rounded-full bg-methaq-sage text-methaq-gold-dark transition-colors duration-500 group-data-[reached=true]/step:bg-methaq-primary group-data-[reached=true]/step:text-white">
                                        <Icon size={24} aria-hidden="true" />
                                    </span>
                                </div>
                                <h3 className="m-0 font-methaq-display text-[24px] font-bold text-methaq-primary">
                                    {step.title}
                                </h3>
                                <p className="m-0 text-[15px] leading-relaxed text-methaq-muted">
                                    {step.description}
                                </p>
                                <p className="m-0 flex items-center gap-2 pt-1 text-[13px] font-medium text-methaq-primary">
                                    <Check
                                        size={16}
                                        aria-hidden="true"
                                        className="text-methaq-gold transition-transform duration-500 group-data-[reached=true]/step:scale-125"
                                    />
                                    {step.detail}
                                </p>
                            </li>
                        );
                    })}
                </ol>
                </div>
            </div>
        </section>
    );
}
