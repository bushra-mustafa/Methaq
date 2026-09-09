import type { EditorAsset } from '../../../Types/Editor';
import type { PresentationCard } from '../../../Types/PresentationCard';
import { useEffect, useRef, useState } from 'react';
import type { DesignDocument } from '../../../Types/DesignDocument';
import { InvitationPlayer } from './InvitationPlayer';

interface Props { assets: EditorAsset[]; cardParts?: PresentationCard | null; scene: DesignDocument['scene']; palette: DesignDocument['palette']; cardUrl: string; title: string; onClose: () => void }

export function InvitationPreview({ onClose, ...invitation }: Props) {
    const dialog = useRef<HTMLDialogElement>(null);
    const [viewport, setViewport] = useState<'phone' | 'desktop'>('phone');
    useEffect(() => {
        const previousFocus = document.activeElement;
        const element = dialog.current;
        element?.showModal();
        return () => {
            element?.close();
            if (previousFocus instanceof HTMLElement) previousFocus.focus();
        };
    }, []);
    return <dialog ref={dialog} className="invitation-preview-dialog" aria-label="معاينة الدعوة كضيف" onCancel={(event) => { event.preventDefault(); onClose(); }}>
        <header className="invitation-preview-toolbar" dir="rtl"><div><strong>معاينة كضيف</strong><small>مسودتك الحالية · قبل النشر</small></div><div className="invitation-preview-devices" role="group" aria-label="حجم المعاينة"><button type="button" aria-pressed={viewport === 'phone'} onClick={() => setViewport('phone')}>هاتف</button><button type="button" aria-pressed={viewport === 'desktop'} onClick={() => setViewport('desktop')}>كمبيوتر</button></div><button type="button" onClick={onClose}>رجوع للتعديل ×</button></header>
        <div className="invitation-preview-surround"><InvitationPlayer {...invitation} viewport={viewport} /></div>
    </dialog>;
}
