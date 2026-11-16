import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { InvitationProgress } from '../../../Types/InvitationProgress';

const steps = ['رفع القسيمة', 'مراجعة الدفع', 'اعتماد الدفع', 'جاهزة للنشر', 'منشورة'];

export function InvitationProgressPanel({ progress, timezone }: { progress: InvitationProgress; timezone: string }) {
    const [refreshing, setRefreshing] = useState(false);
    function refresh(): void {
        setRefreshing(true);
        router.reload({ only: ['event', 'payment', 'progress', 'whatsapp'], onFinish: () => setRefreshing(false) });
    }
    const unavailable = progress.state === 'expired' || progress.state === 'blocked';
    return <section className={`invitation-progress is-${progress.state}`} aria-label="حالة دعوتك">
        <div className="invitation-progress-heading"><div><p className="invitation-progress-eyebrow">حالة دعوتك</p><h2>{progress.title}</h2><p>{progress.detail}</p></div><Link className="account-primary" href={progress.actionUrl}>{progress.actionLabel}</Link></div>
        {!unavailable && <ol className="invitation-progress-steps" aria-label="تقدم الدعوة">{steps.map((label, index) => <li key={label} className={index < progress.step ? 'is-done' : index === progress.step ? 'is-current' : ''} aria-current={index === progress.step ? 'step' : undefined}><span aria-hidden="true">{index < progress.step ? '✓' : index + 1}</span>{label}</li>)}</ol>}
        {progress.reviewDueAt && <p className="invitation-progress-review">متابعة المراجعة: {new Intl.DateTimeFormat('ar-LY', { dateStyle: 'medium', timeStyle: 'short', timeZone: timezone }).format(new Date(progress.reviewDueAt))}. هذا موعد متابعة للفريق، وليس ضماناً لاعتماد الدفع.</p>}
        {progress.state === 'pending_review' && <button type="button" className="account-secondary" onClick={refresh} disabled={refreshing} aria-busy={refreshing}>{refreshing ? 'جاري التحديث…' : 'تحديث حالة المراجعة'}</button>}
    </section>;
}
