export interface InvitationProgress {
    state: 'draft' | 'pending_review' | 'rejected' | 'payment_approved' | 'ready_to_publish' | 'published' | 'expired' | 'blocked';
    title: string;
    detail: string;
    step: number;
    actionLabel: string;
    actionUrl: string;
    reviewDueAt: string | null;
}
