import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { StrictMode } from 'react';
import type { ComponentType } from 'react';
import { NavigationLoader } from './Components/Loading/NavigationLoader';

const pages = import.meta.glob<{ default: ComponentType }>('./Pages/**/*.tsx');

void createInertiaApp({
    title: (title) => title ? `${title} — ميثاق` : 'ميثاق — Methaq',
    resolve: async (name) => {
        const loadPage = pages[`./Pages/${name}.tsx`];
        if (!loadPage) throw new Error(`Unknown Inertia page: ${name}`);
        return (await loadPage()).default;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<StrictMode><App {...props} /><NavigationLoader /></StrictMode>);
        document.getElementById('boot-loader')?.remove();
    },
    progress: { color: '#074B36' },
}).catch(() => {
    document.getElementById('boot-loader')?.remove();
    const root = document.getElementById('app');
    if (root) root.textContent = 'تعذّر تحميل الصفحة. حدّث الصفحة للمحاولة مرة أخرى.';
});
