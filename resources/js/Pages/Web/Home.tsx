import { Head, Link, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import type { SharedPageProps } from '../../Types/PageProps';

interface HomeProps {
    showPrototype: boolean;
}

export default function Home({ showPrototype }: HomeProps) {
    const { auth } = usePage<SharedPageProps>().props;

    return (
        <PublicLayout>
            <Head title="عهد الفرح" />
            <section className="brand-page brand-intro" aria-labelledby="intro-title">
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
        </PublicLayout>
    );
}
