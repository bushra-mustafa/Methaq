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

    return <div className="account-page">
        <a href="#account-main" className="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[60] focus:rounded-md focus:bg-methaq-primary focus:px-5 focus:py-3 focus:text-[15px] focus:font-semibold focus:text-white">الانتقال إلى المحتوى</a>
        <header className="account-nav">
            <Link href="/app" aria-label="لوحة ميثاق"><img src="/brand/logos/methaq-horizontal-compact-flat.svg" alt="" width="176" height="39" /></Link>
            <nav aria-label="حسابي">
                <Link href="/app" className={isCurrentPage('/app') ? 'is-active' : undefined}>مناسباتي</Link>
                <Link href="/app/profile" className={isCurrentPage('/app/profile') ? 'is-active' : undefined}>الحساب والأمان</Link>
                {auth.user?.role === 'admin' && <a href="/admin">لوحة الإدارة</a>}
                <Link href="/logout" method="post" as="button">خروج</Link>
            </nav>
        </header>
        <main id="account-main" tabIndex={-1} className="account-content">
            <header className="account-title"><h1>{title}</h1></header>
            {children}
        </main>
    </div>;
}
