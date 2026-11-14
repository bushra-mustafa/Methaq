/** The site's loading ring. Announces `label` to screen readers; pass a visible label next to it when space allows. */
export function Spinner({ label = 'جارٍ التحميل…' }: { label?: string }) {
    return <span className="methaq-spinner" role="status" aria-label={label} />;
}

/** A block shown in place of content that is still being prepared. */
export function LoadingState({ message, role = 'status' }: { message: string; role?: 'status' | 'alert' }) {
    return <div className="methaq-loading-state" role={role}><span className="methaq-spinner" aria-hidden="true" /><p style={{ margin: 0 }}>{message}</p></div>;
}
