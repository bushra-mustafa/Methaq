import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import '../../../../css/auth.css';

interface AuthShellProps {
    title: string;
    description: string;
    children: ReactNode;
    footer?: ReactNode;
}

export function AuthShell({ title, description, children, footer }: AuthShellProps) {
    return <main className="auth-page">
        <section className="auth-story" aria-label="ميثاق">
            <Link href="/" className="auth-logo" aria-label="العودة إلى ميثاق">
                <img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="ميثاق — Methaq" width="245" height="54" />
            </Link>
            <div className="auth-story-copy">
                <span className="auth-kicker">عهد يبدأ بتفصيلة</span>
                <p>اصنعي دعوة تحمل لون مناسبتك، كلماتك، وطريقتك الخاصة في استقبال من تحبين.</p>
            </div>
            <div className="auth-seal" aria-hidden="true"><span>م</span></div>
        </section>
        <section className="auth-panel">
            <div className="auth-card">
                <header><p>حساب ميثاق</p><h1>{title}</h1><span>{description}</span></header>
                {children}
                {footer && <footer>{footer}</footer>}
            </div>
        </section>
    </main>;
}
