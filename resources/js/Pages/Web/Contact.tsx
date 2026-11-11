import { Head, Link } from "@inertiajs/react";
import PublicLayout from "../../Layouts/PublicLayout";

export default function Contact() {
    return (
        <PublicLayout>
            <Head title="تواصل معنا | ميثاق" />

            <section className="mx-auto min-h-screen max-w-4xl px-6 py-16">
                <Link href="/" className="text-sm">
                    الرئيسية
                </Link>

                <h1 className="mt-10 text-4xl font-bold">تواصل معنا</h1>

                <p className="mt-6 leading-8">
                    يسعدنا تواصلك معنا. ستُضاف قنوات التواصل الرسمية عند
                    اعتمادها.
                </p>
            </section>
        </PublicLayout>
    );
}
