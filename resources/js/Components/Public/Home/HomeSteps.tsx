import { useEffect, useRef, useState } from "react";
import { Check, Palette, PenLine, Send } from "lucide-react";
import type { LucideIcon } from "lucide-react";
import Reveal from "../Reveal";
import usePrefersReducedMotion from "../usePrefersReducedMotion";

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

/**
 * A single gold thread runs through the three steps and fills as the visitor reads:
 * 1 → 2 → 3, then it starts again. The steps are a real sequence, so they stay
 * numbered; there are no boxes, only the thread, a node and the text.
 */
export default function HomeSteps() {
    const list = useRef<HTMLDivElement>(null);
    const reducedMotion = usePrefersReducedMotion();
    const [inView, setInView] = useState(false);
    const [active, setActive] = useState(0);
    const lastStep = steps.length - 1;
    const current = reducedMotion ? lastStep : active;
    const progress = `${(current / lastStep) * 100}%`;

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
            className="scroll-mt-24 px-6 pb-methaq-section lg:px-12"
        >
            <div className="mx-auto flex max-w-7xl flex-col gap-16 lg:gap-20">
                <Reveal className="flex max-w-xl flex-col gap-4">
                    <h2
                        id="home-steps-title"
                        className="m-0 font-methaq-display text-methaq-h2 font-bold tracking-[-0.01em] text-balance text-methaq-primary"
                    >
                        كيف تصل دعوتك
                    </h2>
                    <p className="m-0 text-methaq-lead text-pretty text-methaq-muted">
                        من اختيار القالب إلى رابط يفتحه ضيوفك، بدون تعقيد.
                    </p>
                </Reveal>

                <div ref={list} className="relative">
                    <span
                        aria-hidden="true"
                        className="absolute inset-y-6 start-6 w-px bg-methaq-gold/25 md:hidden"
                    >
                        <span
                            className="absolute inset-x-0 top-0 bg-methaq-gold transition-[height] duration-[1200ms] ease-in-out motion-reduce:transition-none"
                            style={{ height: progress }}
                        />
                    </span>
                    <span
                        aria-hidden="true"
                        className="absolute inset-x-[16.66%] top-6 hidden h-px bg-methaq-gold/25 md:block"
                    >
                        <span
                            className="absolute inset-y-0 start-0 bg-methaq-gold transition-[width] duration-[1200ms] ease-in-out motion-reduce:transition-none"
                            style={{ width: progress }}
                        />
                    </span>

                    <ol className="relative m-0 grid list-none gap-12 p-0 md:grid-cols-3 md:gap-10">
                        {steps.map((step, index) => {
                            const Icon = step.icon;
                            const reached = index <= current;

                            return (
                                <li
                                    key={step.numeral}
                                    data-reached={reached}
                                    aria-current={index === current ? "step" : undefined}
                                    className="group/step relative flex flex-col gap-4 ps-20 md:items-center md:ps-0 md:text-center"
                                >
                                    <span
                                        aria-hidden="true"
                                        className="absolute start-0 top-0 grid size-12 place-items-center rounded-full border border-methaq-gold/40 bg-methaq-ivory text-methaq-gold-dark transition-[background-color,border-color,color] duration-500 group-data-[reached=true]/step:border-methaq-primary group-data-[reached=true]/step:bg-methaq-primary group-data-[reached=true]/step:text-methaq-cream motion-reduce:transition-none md:static"
                                    >
                                        <Icon size={22} />
                                    </span>
                                    <span
                                        aria-hidden="true"
                                        className="font-methaq-display text-[56px] leading-none font-bold text-methaq-gold/30 tabular-nums transition-colors duration-500 group-data-[reached=true]/step:text-methaq-gold motion-reduce:transition-none"
                                    >
                                        {step.numeral}
                                    </span>
                                    <h3 className="m-0 font-methaq-display text-methaq-h3 font-bold text-methaq-primary">
                                        {step.title}
                                    </h3>
                                    <p className="m-0 max-w-[34ch] text-[16px] leading-[1.9] text-methaq-muted">
                                        {step.description}
                                    </p>
                                    <p className="m-0 flex items-center gap-2 text-[14px] font-medium text-methaq-primary">
                                        <Check
                                            size={16}
                                            aria-hidden="true"
                                            className="text-methaq-gold-dark"
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
