import type { RefObject } from 'react';
interface Props { host: RefObject<HTMLDivElement | null>; ready: boolean; error: string; retry: () => void }
export function IvoryInvitation({ host, ready, error, retry }: Props) {
    return <section className="ivory-invitation" aria-label="معاينة الدعوة الكاملة">
        <div className="ivory-canvas-host" ref={host} />
        {!ready && !error && <div className="ivory-canvas-loading" role="status">نجهّز تفاصيل دعوتك…</div>}
        {error && <div className="ivory-canvas-error" role="alert"><p>{error}</p><button type="button" onClick={retry}>إعادة المحاولة</button></div>}
    </section>;
}
