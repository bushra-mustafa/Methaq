import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/** After a short delay (so quick pages never flash), tells the visitor that a page is on its way. */
export function NavigationLoader() {
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        let timer: number | undefined;
        const stop = router.on('start', (event) => {
            const visit = event.detail.visit;
            if (visit.async || visit.prefetch) return;
            window.clearTimeout(timer);
            timer = window.setTimeout(() => setVisible(true), 400);
        });
        const finish = router.on('finish', () => {
            window.clearTimeout(timer);
            setVisible(false);
        });

        return () => {
            stop();
            finish();
            window.clearTimeout(timer);
        };
    }, []);

    return <div aria-live="polite">{visible && <div className="methaq-nav-loader"><div><span className="methaq-spinner" aria-hidden="true" /><span>جارٍ التحميل…</span></div></div>}</div>;
}
