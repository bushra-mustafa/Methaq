import { Head, Link } from "@inertiajs/react";

export default function Contact() {
    return (
        <main dir="rtl" className="min-h-screen px-6 py-16">
            <Head title="تواصل معنا | ميثاق" />

            <div className="mx-auto max-w-4xl">
                <Link href="/" className="text-sm">
                    الرئيسية
                </Link>

                <h1 className="mt-10 text-4xl font-bold">تواصل معنا</h1>

                <p className="mt-6 leading-8">
                    يسعدنا تواصلك معنا. ستُضاف قنوات التواصل الرسمية عند
                    اعتمادها.
                </p>
            </div>
        </main>
    );
}
