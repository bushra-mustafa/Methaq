import type { ReactNode } from 'react';
import type { ContactIconKey } from '../../Types/Support';

const glyphs: Record<ContactIconKey, ReactNode> = {
    envelope: <><rect x="3" y="5.5" width="18" height="13" rx="2" /><path d="m3.5 7 8.5 6 8.5-6" /></>,
    phone: <path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v3a2 2 0 0 1-2 2A15 15 0 0 1 3 6a2 2 0 0 1 2-2z" />,
    whatsapp: <><path d="M3.5 20.5 5 16a8.5 8.5 0 1 1 3.2 3.2z" /><path d="M9 8.5c0 3.2 2.8 6 6 6l1.4-1.4-2-1-.9.8c-1-.4-1.9-1.3-2.3-2.3l.8-.9-1-2z" /></>,
    instagram: <><rect x="3.5" y="3.5" width="17" height="17" rx="5" /><circle cx="12" cy="12" r="4" /><circle cx="17.2" cy="6.8" r=".6" /></>,
    facebook: <path d="M15 4h-2a4 4 0 0 0-4 4v3H7v3h2v6h3v-6h2.5l.5-3h-3V8a1 1 0 0 1 1-1h2z" />,
    telegram: <><path d="M21 4 3 11l6 2 2 6 3-4 5 3z" /><path d="m9 13 12-9" /></>,
    x: <path d="M5 4.5l14 15M19 4.5l-14 15" />,
    tiktok: <path d="M14 4v10.5a3.5 3.5 0 1 1-3.5-3.5M14 4c.4 2.6 2 4.2 5 4.5" />,
    snapchat: <path d="M12 3.5c-3 0-4.5 2.2-4.5 5v2.2c-1 .2-2 .5-2.5 1 .8.8 1.8 1 2.5 1.1-.3 1.4-1.3 2.4-2.5 3 1 .6 2.2.7 3 .8.4.7 1 1.3 2 1.3s1.2-.7 2-.7 1 .7 2 .7 1.6-.6 2-1.3c.8-.1 2-.2 3-.8-1.2-.6-2.2-1.6-2.5-3 .7-.1 1.7-.3 2.5-1.1-.5-.5-1.5-.8-2.5-1V8.5c0-2.8-1.5-5-4.5-5z" />,
    youtube: <><path d="M3 8.5A3 3 0 0 1 6 5.5h12a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3z" /><path d="m10 9.5 5 2.5-5 2.5z" /></>,
    globe: <><circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18" /></>,
};

export default function ChannelIcon({ icon, className }: { icon: ContactIconKey; className?: string }) {
    return <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className={className ?? 'size-5'}>{glyphs[icon]}</svg>;
}
