import { Head, Link, usePage } from '@inertiajs/react';
import type { SharedPageProps } from '../../Types/PageProps';

interface HomeProps {
    showPrototype: boolean;
}

export default function Home({ showPrototype }: HomeProps) {
    const { auth } = usePage<SharedPageProps>().props;

    return (
        <>
            <Head title="عهد الفرح" />
            <main className="brand-page">
                <header className="brand-header">
                    <img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="ميثاق — Methaq" width="260" height="57" />
                    <nav aria-label="الحساب">
                        {auth.user
                            ? <Link className="brand-account-link" href="/app">مناسباتي</Link>
                            : <><Link className="brand-login-link" href="/login">دخول</Link><Link className="brand-account-link" href="/register">ابدئي الآن</Link></>}
                    </nav>
                </header>
                <section className="brand-intro" aria-labelledby="intro-title">
                    <div className="invitation-mark" aria-hidden="true">
                        <img src="/brand/logos/methaq-symbol-flat.svg" alt="" width="230" />
                    </div>
                    <p className="brand-eyebrow">ميثاق · عهد الفرح</p>
                    <h1 id="intro-title">لكل فرحة،<br />دعوة تشبهها.</h1>
                    <p className="brand-description">مساحة لتصميم دعواتكم بالألوان والكلمات والتفاصيل التي تحبّونها.</p>
                    <div className="brand-actions">
                        <Link className="brand-primary-action" href={auth.user ? '/app' : '/register'}>{auth.user ? 'افتحي مناسباتك' : 'أنشئي حسابك'}</Link>
                        <Link className="brand-secondary-action" href="/templates">استعرضي القوالب</Link>
                        {showPrototype && <a className="brand-secondary-action" href="/dev/design-lab">شاهدي البطاقة التجريبية</a>}
                    </div>
                </section>
                <footer className="brand-footer">ميثاق <span lang="en" dir="ltr">Methaq</span></footer>
            </main>
        </>
    );
}
