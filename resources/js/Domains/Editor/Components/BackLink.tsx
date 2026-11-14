import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

interface BackLinkProps {
    href: string;
    children: ReactNode;
    canLeave?: boolean;
    label?: string;
    title?: string;
}

/**
 * Pages under /admin belong to the Filament panel, which is not an Inertia page: an Inertia link to it would
 * show the panel's HTML inside a white modal. Those links use a normal browser navigation instead.
 */
export function BackLink({ href, children, canLeave = true, label, title }: BackLinkProps) {
    if (href.startsWith('/admin')) {
        return <a href={href} aria-label={label} title={title} aria-disabled={!canLeave} onClick={(event) => { if (!canLeave) event.preventDefault(); }}>{children}</a>;
    }

    return <Link href={href} aria-label={label} title={title} aria-disabled={!canLeave} onBefore={() => canLeave}>{children}</Link>;
}
