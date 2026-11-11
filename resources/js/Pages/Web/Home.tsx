import { Head, Link, usePage } from "@inertiajs/react";
import PublicLayout from "../../Layouts/PublicLayout";
import type { SharedPageProps } from "../../Types/PageProps";
import HomeHero from "../../Components/Public/Home/HomeHero";

interface HomeProps {
    showPrototype: boolean;
}

const secondaryActionClassName =
    "inline-flex min-h-12 min-w-[150px] items-center justify-center border border-methaq-primary px-5 py-3 text-sm font-bold text-methaq-primary transition-colors hover:bg-methaq-sage";

export default function Home({ showPrototype }: HomeProps) {
    const { auth } = usePage<SharedPageProps>().props;

    return (
        <PublicLayout>
            <Head title="عهد الفرح" />

            <HomeHero
                isAuthenticated={Boolean(auth.user)}
                showPrototype={showPrototype}
            />

            <section
                className="mx-auto w-full max-w-3xl px-6 py-14 text-center"
                aria-labelledby="intro-title"
            >
                <div className="mb-8 grid place-items-center" aria-hidden="true">
                    <img
                        src="/brand/logos/methaq-symbol-flat.svg"
                        alt=""
                        width="180"
                        className="h-auto w-[180px]"
                    />
                </div>
                <p className="mb-4 text-sm text-methaq-primary">
                    ميثاق · عهد الفرح
                </p>
                <h2
                    id="intro-title"
                    className="mb-6 font-methaq-display text-[clamp(40px,6vw,68px)] font-medium leading-[1.55] text-methaq-primary"
                >
                    لكل فرحة،
                    <br />
                    دعوة تشبهها.
                </h2>
                <p className="mx-auto max-w-[480px] text-lg leading-loose text-methaq-primary">
                    مساحة لتصميم دعواتكم بالألوان والكلمات والتفاصيل التي
                    تحبّونها.
                </p>
                <div className="mt-9 flex flex-col items-stretch justify-center gap-3 sm:flex-row sm:items-center">
                    <Link
                        className="inline-flex min-h-12 min-w-[150px] items-center justify-center border border-methaq-primary bg-methaq-primary px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-methaq-primary-hover"
                        href={auth.user ? "/app" : "/register"}
                    >
                        {auth.user ? "افتحي مناسباتك" : "أنشئي حسابك"}
                    </Link>
                    <Link className={secondaryActionClassName} href="/templates">
                        استعرضي القوالب
                    </Link>
                    {showPrototype && (
                        <a
                            className={secondaryActionClassName}
                            href="/dev/design-lab"
                        >
                            شاهدي البطاقة التجريبية
                        </a>
                    )}
                </div>
            </section>
        </PublicLayout>
    );
}
