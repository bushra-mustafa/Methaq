const steps = [
    {
        numeral: "1",
        title: "اختاري قالباً لمناسبتك",
        description:
            "زفاف أو حنّة أو عقد قران أو تخرج، ولكل قالب ألوانه وزخارفه.",
    },
    {
        numeral: "2",
        title: "خصّصي البطاقة والظرف",
        description:
            "غيّري النصوص والألوان والخطوط، واختاري شكل الظرف والختم والموسيقى.",
    },
    {
        numeral: "3",
        title: "انشري وشاركي الرابط",
        description:
            "لمناسبتك رابط خاص يفتحه ضيوفك، ويؤكدون حضورهم من الصفحة نفسها.",
    },
];

export default function HomeSteps() {
    return (
        <section
            aria-labelledby="home-steps-title"
            className="border-t border-methaq-gold/20 px-6 py-20 lg:py-24"
        >
            <div className="mx-auto max-w-5xl text-center">
                <h2
                    id="home-steps-title"
                    className="m-0 font-methaq-display text-[clamp(26px,3.4vw,32px)] font-normal text-methaq-primary"
                >
                    كيف تصل دعوتك
                </h2>

                <ol className="relative mt-14 grid list-none gap-12 p-0 lg:grid-cols-3 lg:gap-8">
                    <span
                        aria-hidden="true"
                        className="absolute inset-x-[16%] top-6 hidden h-px bg-methaq-gold/40 lg:block"
                    />
                    {steps.map((step) => (
                        <li
                            key={step.numeral}
                            className="relative flex flex-col items-center gap-4"
                        >
                            <span
                                aria-hidden="true"
                                className="grid size-12 place-items-center rounded-full border border-methaq-gold/60 bg-methaq-ivory text-[18px] font-semibold tabular-nums text-methaq-gold-dark"
                            >
                                {step.numeral}
                            </span>
                            <h3 className="m-0 text-[21px] font-normal text-methaq-primary">
                                {step.title}
                            </h3>
                            <p className="m-0 max-w-[32ch] text-[15px] leading-[1.9] text-methaq-muted">
                                {step.description}
                            </p>
                        </li>
                    ))}
                </ol>
            </div>
        </section>
    );
}
