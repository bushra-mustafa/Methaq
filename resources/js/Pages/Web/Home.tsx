import { Head } from '@inertiajs/react';

export default function Home() {
    return (
        <>
            <Head title="عهد الفرح" />
            <main className="brand-page">
                <header className="brand-header">
                    <img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="ميثاق — Methaq" width="260" height="57" />
                    <span>دعوات رقمية</span>
                </header>
                <section className="brand-intro" aria-labelledby="intro-title">
                    <div className="invitation-mark" aria-hidden="true">
                        <img src="/brand/logos/methaq-symbol-flat.svg" alt="" width="230" />
                    </div>
                    <p className="brand-eyebrow">ميثاق · عهد الفرح</p>
                    <h1 id="intro-title">لكل فرحة،<br />دعوة تشبهها.</h1>
                    <p className="brand-description">مساحة لتصميم دعواتكم بالألوان والكلمات والتفاصيل التي تحبّونها.</p>
                    <p className="brand-notice">نعمل على تجهيز المنصة. نلتقي قريباً.</p>
                </section>
                <footer className="brand-footer">ميثاق <span lang="en" dir="ltr">Methaq</span></footer>
            </main>
        </>
    );
}
