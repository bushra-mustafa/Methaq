import { useEffect, useRef, useState } from 'react';
import type { ComponentType } from 'react';
import type { EnvelopeMotionSceneProps } from '../../../Types/EnvelopeMotion';
import '../../../../css/envelope-motion-preview.css';

interface Props extends EnvelopeMotionSceneProps { onClose: () => void }

export function EnvelopeMotionPreview({ onClose, ...scene }: Props) {
    const dialog = useRef<HTMLDialogElement>(null);
    const [viewport, setViewport] = useState<'phone' | 'desktop'>('phone');
    const [Scene, setScene] = useState<ComponentType<EnvelopeMotionSceneProps> | null>(null);
    const [failed, setFailed] = useState(false);
    const [attempt, setAttempt] = useState(0);

    useEffect(() => {
        let active = true;
        setFailed(false);
        void import('./EnvelopeMotionScene').then((module) => {
            if (active) setScene(() => module.default);
        }).catch(() => { if (active) setFailed(true); });
        return () => { active = false; };
    }, [attempt]);

    useEffect(() => {
        const previousFocus = document.activeElement;
        const element = dialog.current;
        element?.showModal();
        return () => {
            element?.close();
            if (previousFocus instanceof HTMLElement) previousFocus.focus();
        };
    }, []);

    return <dialog ref={dialog} className="envelope-motion-dialog" aria-labelledby="envelope-motion-title" dir="rtl" onCancel={(event) => { event.preventDefault(); onClose(); }}>
        <header className="envelope-motion-toolbar">
            <div><strong id="envelope-motion-title">تجربة الفتح الجديدة</strong><small>كلاسيكي عاجي · معاينة فقط</small></div>
            <div className="envelope-motion-devices" role="group" aria-label="حجم المعاينة"><button type="button" aria-pressed={viewport === 'phone'} onClick={() => setViewport('phone')}>هاتف</button><button type="button" aria-pressed={viewport === 'desktop'} onClick={() => setViewport('desktop')}>كمبيوتر</button></div>
            <button className="envelope-motion-close" type="button" onClick={onClose} aria-label="إغلاق تجربة الفتح" autoFocus>رجوع للتعديل <span aria-hidden="true">×</span></button>
        </header>
        <div className="envelope-motion-surround">
            <div className={`envelope-motion-frame is-${viewport}`}>
                {Scene ? <Scene {...scene} /> : <div className="envelope-motion-notice" role={failed ? 'alert' : 'status'}><p>{failed ? 'تعذّر تحميل تجربة الفتح.' : 'نجهّز تجربة الفتح…'}</p>{failed && <button type="button" onClick={() => setAttempt((value) => value + 1)}>إعادة المحاولة</button>}</div>}
            </div>
        </div>
    </dialog>;
}
