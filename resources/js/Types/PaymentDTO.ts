export type PaymentState = 'paid' | 'pending_review' | 'rejected' | 'awaiting_receipt';
export type OrderStatus = 'pending' | 'completed' | 'failed';

export interface BankAccountDTO {
    bankName: string | null;
    accountName: string | null;
    accountNumber: string;
    iban: string | null;
}

export interface LatestOrderDTO {
    amountLabel: string;
    status: OrderStatus;
    submittedAt: string;
    reviewedAt: string | null;
}

export interface EventPaymentDTO {
    state: PaymentState;
    priceLabel: string | null;
    bankAccount: BankAccountDTO | null;
    includedFeatures: string[];
    lockedFeatures: string[];
    canSubmit: boolean;
    blocker: string | null;
    latestOrder: LatestOrderDTO | null;
}
