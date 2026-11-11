import { Head } from "@inertiajs/react";
import PublicLayout from "../../Layouts/PublicLayout";

export default function About() {
    return (
        <PublicLayout>
            <Head title="من نحن" />
            <section className="mx-auto max-w-4xl px-6 py-16">
                <h1>من نحن</h1>
                <p>مرحبًا بكم في ميثاق</p>
            </section>
        </PublicLayout>
    );
}
