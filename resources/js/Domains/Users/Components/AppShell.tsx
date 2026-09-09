import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { SharedPageProps } from '../../../Types/PageProps';
import '../../../../css/account.css';

interface AppShellProps {
    title: string;
    eyebrow: string;
    children: ReactNode;
}

export function AppShell({ title, eyebrow, children }: AppShellProps) {
    const page = usePage<SharedPageProps>();
    const { auth } = page.props;
    const isCurrentPage = (path: string): boolean => page.url === path || (path !== '/app' && page.url.startsWith(`${path}/`));

    return <main className="account-page">
        <header className="account-nav">
            <Link href="/app" aria-label="لوحة ميثاق"><img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="ميثاق" /></Link>
            <nav aria-label="حسابي">
                <Link href="/app" className={isCurrentPage('/app') ? 'is-active' : undefined}>مناسباتي</Link>
                <Link href="/app/profile" className={isCurrentPage('/app/profile') ? 'is-active' : undefined}>الحساب والأمان</Link>
                {auth.user?.role === 'admin' && <><Link href="/app/admin" className={isCurrentPage('/app/admin') ? 'is-active' : undefined}>الإدارة</Link><Link href="/app/admin/library" className={isCurrentPage('/app/admin/library') ? 'is-active' : undefined}>المكتبة</Link></>}
                <Link href="/logout" method="post" as="button">خروج</Link>
            </nav>
        </header>
        <div className="account-content">
            <header className="account-title"><span>{eyebrow}</span><h1>{title}</h1></header>
            {children}
        </div>
    </main>;
}
