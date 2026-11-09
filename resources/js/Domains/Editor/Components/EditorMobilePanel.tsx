import { useEffect, useRef, type ReactNode } from 'react';

interface Props { open: boolean; onClose: () => void; children: ReactNode }

export function EditorMobilePanel({ open, onClose, children }: Props) {
    const dialog = useRef<HTMLDialogElement>(null);

    useEffect(() => {
        if (!open) return;
        const element = dialog.current;
        const previousFocus = document.activeElement;
        element?.showModal();
        return () => {
            element?.close();
            if (previousFocus instanceof HTMLElement && previousFocus.isConnected) previousFocus.focus();
        };
    }, [open]);

    useEffect(() => {
        const desktop = window.matchMedia('(min-width: 741px)');
        const closeOnDesktop = (): void => { if (desktop.matches) onClose(); };
        desktop.addEventListener('change', closeOnDesktop);
        return () => desktop.removeEventListener('change', closeOnDesktop);
    }, [onClose]);

    return <dialog ref={dialog} className="editor-mobile-panel" aria-label="لوحة عناصر البطاقة" onCancel={onClose}>
        <header><strong>العناصر والتفاصيل</strong><button type="button" aria-label="إغلاق لوحة العناصر" onClick={onClose}>×</button></header>
        {open && children}
    </dialog>;
}
