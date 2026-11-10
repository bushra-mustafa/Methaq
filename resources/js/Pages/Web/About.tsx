
import { Head, Link } from '@inertiajs/react';

export default function About() {
    return (
        <main dir="rtl" className="min-h-screen px-6 py-16">
            <Head title="من نحن | ميثاق" />

            <div className="mx-auto max-w-4xl">
                <Link href="/" className="text-sm">
                    الرئيسية
                </Link>

                <h1 className="mt-10 text-4xl font-bold">
                    من نحن
                </h1>

                <p className="mt-6 leading-8">
                    ميثاق منصة لتصميم الدعوات الإلكترونية
                    الأنيقة وتخصيصها ومشاركتها مع الأحباب.
                </p>

                <Link href="/templates" className="mt-8 inline-block underline">
                    استكشف القوالب
                </Link>
            </div>
        </main>
    );
}
