import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { SharedPageProps } from '../../../Types/PageProps';
import '../../../../css/account.css';

interface AppShellProps {
    title: string;
    /** Kept for existing callers; the page title carries the meaning now. */
    eyebrow?: string;
    children: ReactNode;
}

export function AppShell({ title, children }: AppShellProps) {
    const page = usePage<SharedPageProps>();
    const { auth } = page.props;
    const isCurrentPage = (path: string): boolean => page.url === path || (path !== '/app' && page.url.startsWith(`${path}/`));

    return <main className="account-page">
        <header className="account-nav">
            <Link href="/app" aria-label="لوحة ميثاق"><img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="" width="176" height="39" /></Link>
            <nav aria-label="حسابي">
                <Link href="/app" className={isCurrentPage('/app') ? 'is-active' : undefined}>مناسباتي</Link>
                <Link href="/app/profile" className={isCurrentPage('/app/profile') ? 'is-active' : undefined}>الحساب والأمان</Link>
                {auth.user?.role === 'admin' && <a href="/admin">لوحة الإدارة</a>}
                <Link href="/logout" method="post" as="button">خروج</Link>
            </nav>
        </header>
        <div className="account-content">
            <header className="account-title"><h1>{title}</h1></header>
            {children}
        </div>
    </main>;
}
